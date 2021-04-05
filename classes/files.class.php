<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w, $t; // structure - json; $_FILES; warnings; dir for temporary files
    public $files; // [ single file array + post field name ]

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        //$this->t = wp_get_upload_dir()['basedir'] . '/' . FCP_Forms::$tmp_dir;
        $this->proceed();
        

    }

    private function proceed() {

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

            if ( $v[0] && !$fields[$k] ) { // field is not multiple in structure
                $f[$k] = $v[0];
                continue;
            }
            if ( $v['name'] && $fields[$k] ) { // field is multiple in structure
                unset( $f[$k] );
                $f[$k] = [];
                $f[$k][0] = $v;
            }
        }

        // flatten
        $fl = [];
        foreach ( $f as $k => $v ) {
            if ( $v['name'] ) {
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
            if ( !$this->w[ $v['field'] ] ) { // no warnings for the file by field
                continue;
            }
            if ( !in_array( $v['name'], $this->w[ $v['field'] ] ) ) { // no warnings for the file by name
                continue;
            }
            unset( $f[$k] );
        }

        //$this->files = array_values( $f );
        $f = array_values( $f );
        echo '<pre>';
        print_r( $this->f );
        print_r( $f );
        echo '</pre>';
        exit;

    }
    
    public static function flatten_files($f, $field = '', &$return = []) {
        if ( $f[0] && $field ) {
            self::flatten_files( $f, $field );
        } else {
            if ( is_array( $f ) ) {
                $return[] =  $f;
            }
        }
    }
    
    private function filter_files() {

        foreach ( $this->s->fields as $f ) {

            if ( $f->type != 'file' ) {
                continue;
            }

            // multiple files
            if ( $f->multiple ) {
            
                foreach ( $mflip as $v ) {
                    if ( $this->addResult( $method, $f->name, $rule, $v ) ) {
                        $this->mFilesFailed[ $f->name ][] = $v['name'];
                    }
                }

                continue;
            }
            
            // single file
        }
    }
    
    private function uploadTemporary() {
        
        foreach ( $this->f as $k => $v ) {
            if ( $v[0] ) { // multiple upload
            
               // $this->result[$k] = 
            }
        }
        
        echo '<pre>';
        print_r( $this->f );
        print_r( $this->w );
        print_r( $this->t );
        echo '</pre>';
        exit;
        
        /*
            upload if no warnings about them as time-md5(rand).ext
            replace, if hidden value is provided & file exists, else ^ OR add more if is multiple and not repeating name, delete if empty or "delete" checkbox is clicked??
            fill in the hidden field

            remove all 10 minutes outdated - place to the main class
            move the flatten to main class?
            use ::flip_files in validate.class
        */        
    }
    
    private function hiddenValue() {
    
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
