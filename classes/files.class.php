<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w, $d; // json structure; $_FILES; warnings; directories ['field-name' => 'dir']
    public $files, $uploaded; // [] of prepared $_FILES with ['field'], [] of uploaded files ['name','field']

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        $this->d = [];
        $this->process();

    }

    public function add_dirs($a) {
        $this->d = array_merge( $this->d, $a );
    }
    
    public function upload() {
        $bad = [];
    
        foreach ( $this->files as $k => $v ) {
            if ( !move_uploaded_file( $v['tmp_name'], $this->d[ $v['field'] ] . '/' . $v['name'] ) ) {
                $bad[] = $v['name'] . ' not uploaded';
                continue;
            }
            $this->uploaded[] = [ 'name' => $v['name'], 'field' => $v['field'] ];
            unset( $this->files[$k] );
        }
        
        return empty( $bad ) ? true : $bad;
    }

    private function process() {

        // filter files
        
        // by structure: field exists, is multiple
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
        $fl = []; // [0] => [ name, tmp_name, size, error, added field name ]
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

        // by server error
        foreach ( $f as $k => $v ) {
            if ( $v['error'] ) {
                unset( $f[$k] );
            }
        }

        // by warnings
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
    
    public function uploaded_files_set() {
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) { continue; }
            $_POST[ '--' . $v->name ] = [];
        }
        foreach ( $this->uploaded as $v ) {
            $_POST[ '--' . $v['field'] ][] = $v['name'];
        }
    }
    
    public function uploaded_files_get() {
        if ( empty( $this->d ) ) { return; }
    
        $result = [];
        $keep = [];

        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) { continue; }
            if ( !$_POST[ '--' . $v->name ] ) { continue; }

            foreach ( $_POST[ '--' . $v->name ] as $w ) {
                $w = sanitize_file_name( $w );
                $path = $this->d[ $v->name ] . '/' . $w;

                if ( !is_file( $path ) ) { continue; }

                $result[] = [ 'name' => $w, 'field' => $v->name ];
                $keep[ $path ] = true;

                if ( !$v->multiple ) { continue 2; }
            }

        }

        $this->uploaded = $result; // the list of uploaded files
        
        // delete files, not on the list, from server
        foreach ( $this->s->fields as $v ) {
            $dir = $this->d[ $v->name ];
            $files = array_diff( scandir( $dir ), [ '.', '..' ] );

            foreach ( $files as $file ) {
                if ( $keep[ $dir . '/' . $file ] ) { continue; }
                unlink( $dir . '/' . $file );
            }
        }
    }

//--_____---____--__________--Helpers

    public static function rm_dir($dir) { // from https://www.php.net/manual/ru/function.rmdir.php
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
    
    // flip the array of uploading files from [name][0] to [0][name]
    public static function flip_files($mfile = []) {
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
    
    public static function tmp_dir() {
        return [
            0 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir,
            1 => wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir . '/' . $_POST['fcp-form--tmpdir']
        ];
    }
//*/
}
