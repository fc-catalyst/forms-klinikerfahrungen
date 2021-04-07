<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w; // structure - json; $_FILES; warnings; dir for temporary files
    public $files; // [ single file array + post field name ]

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        $this->proceed();

    }

    public static function tmp_dir() {
        return [
            0 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir,
            1 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir . '/' . $_POST['fcp-form--tmpdir']
        ];
    }
    
    public function tmp_upload() {
        if ( !count( $this->files ) ) {
            return;
        }
        
        $result = [];
        
        // mk tmp dir
        $dir = self::tmp_dir()[1];
        if ( !is_dir( $dir ) ) {
            if ( !mkdir( $dir ) ) {
                $result[] = ' tmp folder not created';
            }
        }

        // upload accepted files
        foreach ( $this->files as $k => $v ) {
            if ( !move_uploaded_file( $v['tmp_name'], $dir . '/' . $v['name'] ) ) {
                unlink( $this->files[$k] );
                $result[] = $v['name'] . ' not uploaded to tmp';
            }
        }
        
        $this->files = array_values( $this->files );
        
        return $result[0] ? $result : true;
    }
    
    public function tmp_move($to = '') {
        if ( !count( $this->files ) || !$to ) {
            return;
        }
        
        $result = [];
        
        $dir = self::tmp_dir();
        foreach ( $this->files as $k => $v ) {
            if ( is_file( $dir . '/' . $v['name'] ) && is_dir( $dir[0] . '/' . $to ) ) {
                if ( !rename( $dir[1] . '/' . $v['name'], $dir[0] . '/' . $to . '/' . $v['name'] ) ) {
                    $result[] = $v['name'] . ' not moved from tmp';
                }
            }
        }
        
        return $result[0] ? $result : true;
    }
    
    public static function tmp_clean() {
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
    
    public function for_hiddens() { // use after tmp_upload, as it makes the final list of uploaded files
        $result = [];
        foreach ( $this->files as $v ) {
            $result[ $v['field'] ][] = $v['name'];
        }
        foreach ( $result as $k => &$v ) {
            if ( $_POST[ '--' . $k ] ) {
                 $hidden = json_decode( $_POST[ '--' . $k ], true );
                 $v = array_unique( $hidden + $v );
            }
            $v = json_encode( $v );
            $_POST[ '--' . $k ] = $v;
        }
        return $result;
    }
    
    private function proceed() { // ++brush it up later

        // filter by structure (field, multiple)
        $fields = [];
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) {
                continue;
            }
            $fields[ $v->name ] = $v->multiple ? 1 : 0;
        }

        $f = $this->f;
        foreach ( $f as $k => $v ) {
            if ( !isset( $fields[$k] ) ) { // field doesn't exist in structure
                unset( $f[$k] );
                continue;
            }

            if ( isset( $v[0] ) && !$fields[$k] ) { // field is not multiple in structure
                $f[$k] = $v[0];
                continue;
            }
            if ( isset( $v['name'] ) && $fields[$k] ) { // field is multiple in structure
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

        $this->files = array_values( $f );

    }

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

}
