<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w; // structure - json; $_FILES; warnings; dir for temporary files
    public $files, $tmps; // [ single file array ] + [ post field name ], [ name, field ] for $tmps

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        $this->proceed();

    }

    private function proceed() { // ++brush it and all the loops up later

        // filter by structure (field, multiple)
        $multi = [];
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) {
                continue;
            }
            $multi[ $v->name ] = $v->multiple ? 1 : 0;
        }

        $f = $this->f;
        foreach ( $f as $k => $v ) {
            if ( !isset( $multi[$k] ) ) { // field doesn't exist in structure
                unset( $f[$k] );
                continue;
            }

            if ( isset( $v[0] ) && !$multi[$k] ) { // field is not multiple in structure
                $f[$k] = $v[0];
                continue;
            }
            if ( isset( $v['name'] ) && $multi[$k] ) { // field is multiple in structure
                unset( $f[$k] );
                $f[$k] = [ 0 => $v ];
            }
        }

        // flatten
        $fl = [];
        foreach ( $f as $k => $v ) {
            if ( isset( $v['name'] ) ) {
                $fl[] = $v + ['field' => $k];
                continue;
            }
            foreach ( $v as $w ) {
                $fl[] = $w + ['field' => $k];
            }
        }
        $f = $fl;
        unset( $fl );

        // uploading error
        foreach ( $f as $k => $v ) {
            if ( $v['error'] ) {
                unset( $f[$k] );
            }
        }

        // filter by warnings & error
        foreach ( $f as $k => $v ) {
            if ( !$this->w[ $v['field'] ] ) { // no warnings for the field
                continue;
            }
            if ( !in_array( $v['name'], $this->w[ $v['field'] ] ) ) { // no warnings for the file by name
                continue;
            }
            unset( $f[$k] );
        }
        
        // sanitize files names
        foreach ( $f as &$v ) {
            $v['name'] = sanitize_file_name( $v['name'] );
        }

        $this->files = array_values( $f ); // the list of files ready for uploading

    }
    
//-----___--__--___-___---tmp uploading operations


    public function tmp_upload() { // upload && ->files to ->tmps

        $this->hiddens_to_tmps();
        $this->tmps_clean();

        if ( !count( $this->files ) ) {
            return;
        }

        if ( !FCP_Forms::unique( $_POST['fcp-form--tmpdir'] ) ) {
            return;
        }
        
        $result = [];
        
        // mk tmp dir
        $dir = self::tmp_dir()[1];
        if ( !is_dir( $dir ) ) {
            if ( !mkdir( $dir ) ) {
                $result[] = 'tmp folder not created';
            }
        }

        // upload accepted files
        foreach ( $this->files as $k => $v ) {
            if ( !move_uploaded_file( $v['tmp_name'], $dir . '/' . $v['name'] ) ) {
                unlink( $this->files[$k] );
                $result[] = $v['name'] . ' not uploaded to tmp';
            }
        }
        
        $this->tmps = array_values( array_merge( $this->files, $this->tmps ) );
        $this->tmps_clean();
        $this->hiddens_fill();
        $this->files = [];

        self::tmp_clean();
        
        return $result[0] ? $result : true;
    }

    private function hiddens_to_tmps() {

        $result = [];

        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) {
                continue;
            }
            if ( $val = $_POST[ '--' . $v->name ] ) {
                 $hidden = explode( ', ', $val );
                 foreach ( $hidden as $l => $w ) {
                    if ( !is_file( self::tmp_dir()[1] . '/' . $w ) ) {
                        continue;
                    }
                    $result[] = [ 'name' => $w, 'field' => $v->name ];
                 }
            }

        }

        $this->tmps = $result;
    }

    private function tmps_clean() {
        $single = [];

        foreach ( $this->tmps as $k => &$v ) {

            // crop singles to 1 file
            foreach ( $this->s->fields as $w ) {
                if ( $v['field'] !== $w->name ) {
                    continue;
                }
                if ( $w->multiple ) {
                    continue 2;
                }
                if ( $single[ $v['field'] ] ) {
                    unset( $this->tmps[$k] );
                    continue 2;
                }
                $single[ $v['field'] ] = true;
            }

            // sanitize files names
            $v['name'] = sanitize_file_name( $v['name'] );

            // check if uploaded
            if ( !is_file( self::tmp_dir()[1] . '/' . $v['name'] ) ) {
                unset( $this->tmps[$k] );
            }

        }

        $this->tmps = array_values( $this->tmps );
    }
    
    private function hiddens_fill() {

        $result = $this->tmps_to_meta();

        foreach ( $result as $k => $v ) {
            $_POST[ '--' . $k ] = $v;
        }

    }
    
    public function tmps_to_meta() {
        $result = [];

        foreach ( $this->tmps as $v ) {
            $result[ $v['field'] ][] = $v['name'];
        }
        foreach ( $result as $k => &$v ) {
            $v = implode( ', ', $v );
        }
        
        return $result;
    }
    
    
    public function tmp_move($to = '') { // move files to final destination
        if ( !count( $this->tmps ) || !$to ) {
            return;
        }
        if ( !is_dir( $to ) ) {
            return ['Target directory doesn\'t exist'];
        }
        
        $result = [];

        $dir = self::tmp_dir();
        foreach ( $this->tmps as $k => $v ) {
            if ( !is_file( $dir[1] . '/' . $v['name'] ) ) {
                $result[] = 'Source file ' . $v['name'] . ' doesn\'t exist';
                continue;
            }
            if ( !copy( $dir[1] . '/' . $v['name'], $to . '/' . $v['name'] ) ) {
            // not rename, because 1 file can be in 2 fields
                $result[] = $v['name'] . ' not moved from tmp';
            }
        }
        if ( isset( $result[0] ) ) {
            return $result;
        }
        self::rm_dir( $dir[1] );
        return true;

    }


//--_____---____--__________--Helpers

    public static function rm_dir($dir) { /* from https://www.php.net/manual/ru/function.rmdir.php */
        if ( !is_dir( $dir ) ) {
            return;
        }
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        foreach ( $files as $file ) {
            $rm = $dir . '/' . $file;
            if ( is_dir( $rm ) ) {
                self::rm_dir( $rm );
                continue;
            }
            unlink( $rm );
        }
        return rmdir($dir);
    }

    public static function rm($a) {
        if ( is_file( $a ) ) {
            unlink( $a );
            return;
        }
        if ( is_dir( $a ) ) {
            self::rm_dir( $a );
        }
    }
    
    public static function flip_files($mfile = []) { /* flip the array of uploading files from [name][0] to [0][name] */
        if ( !is_array( $mfile['name'] ) ) {
            return $mfile;
        }
        $mflip = [];
        for ( $i = 0, $j = count( $mfile['name'] ); $i < $j; $i++ ) {
            foreach ( $mfile as $k => $v ) {
                $mflip[$i][$k] = $mfile[$k][$i];
            }
        }
        return $mflip;
    }
    
    public static function ext($name) {
        if ( !$name ) {
            return;
        }
        return pathinfo( $name, PATHINFO_EXTENSION );
    }
    
    public static function tmp_dir() {
        return [
            0 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir,
            1 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir . '/' . $_POST['fcp-form--tmpdir']
        ];
    }

    public static function tmp_clean() { // call every now and then to clear the tmp dir
        $dir = self::tmp_dir()[0];
        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        foreach ( $files as $file ) {
            $rm = $dir . '/' . $file;
            if ( is_dir( $rm ) ) {
                // ++create the file inside with the timestamp name on the dir creation action, or use timestamp in dir name
                if ( time() - filectime( $rm ) > 15 * 60 ) {
                    self::rm_dir( $rm );
                }
            }
        }
    }
    
}
