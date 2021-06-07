<?php
/*
    Operations with files and directories
*/
class FCP_Forms__Files {

    private $s, $f, $w; // json structure; $_FILES; warnings; directories ['field-name' => 'dir']
    public $files, $uploaded; // [] of prepared $_FILES with ['field']; [] of uploaded files ['name','field']

    public function __construct($s, $f, $w = []) {

        $this->s = $s;
        $this->s->fields = FCP_Forms::flatten( $s->fields );
        $this->w = $w;
        $this->f = array_map( 'self::flip_files', $f );
        $this->prepare_files();

    }

    private function prepare_files() {

        // filter $_FILES to $this->files
        
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
        
        // ++ clean tmp dir
    }

    public function upload_tmp() {
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) { continue; }
            if ( !self::tmp_dir()['base'] ) { continue; }

            $dirs[ $v->name ] = self::tmp_dir()['dir'] . '/' . $v->name;
        }
        return $this->upload( $dirs );
    }

    public function upload($dirs = []) { // [ field => dir, field => dir ]
        if ( empty( $dirs ) ) { return; }
    
        $bad = [];
//++ here somewhere add the move option from tmp dir
    //https://github.com/VVolkov833/wp-fcp-forms/blob/72db7cf33f708fd1e738bd7f4902104d964459c4/classes/files.class.php

        foreach ( $this->s->fields as $v ) { // mk dirs
            if ( $v->type !=='file' ) { continue; }

            if ( !is_dir( $dirs[ $v->name ] ) ) {
                if ( !mkdir( $dirs[ $v->name ], 0777, true ) ) {
                    return [ 'The folder ' . $dirs[ $v->name ] . ' can not be created' ];
                }
            }
        }

        $this->uploaded_files_get( $dirs ); // get & clear the list of uploaded files

        foreach ( $this->files as $k => $v ) { // upload new files
            if ( !move_uploaded_file( $v['tmp_name'], $dirs[ $v['field'] ] . '/' . $v['name'] ) ) {
                $bad[] = $v['name'] . ' not uploaded';
                continue;
            }

            // change the list of uploaded files and files
            foreach ( $this->uploaded as &$w ) { // move re-uploaded file to bottom & remove the old copy
                if ( $w['name'] === $v['name'] && $w['field'] === $v['field'] ) {
                    $w = null;
                }
            }
            $this->uploaded[] = [ 'name' => $v['name'], 'field' => $v['field'] ];
            unset( $this->files[$k] );
        }
        $this->uploaded = array_values( array_filter( $this->uploaded ) );
        
        // remove the files out of limit (single < 2 && with limit < 10 default)
        $count = []; // number of files per field
        $this->uploaded = array_reverse( $this->uploaded );
        foreach ( $this->s->fields as $v ) {
            $count[ $v->name ] = 0;
            if ( !isset( $v->limit ) ) {
                $v->limit = 10;
            }
            if ( !isset( $v->multiple ) ) {
                $v->limit = 1;
            }
            foreach ( $this->uploaded as &$w ) {
                if ( $w['field'] === $v->name ) {
                    $count[ $v->name ]++;
                    if ( $v->limit && $count[ $v->name ] > $v->limit ) { // 0 is for infinite
                        $w = null;
                    }
                }
            }
        }
        $this->uploaded = array_values( array_filter( array_reverse( $this->uploaded ) ) );

        $this->uploaded_files_set(); // set globals for printing the uploaded list to the form

        return empty( $bad ) ? true : $bad;
    }

    private function uploaded_files_get($dirs = []) {

        $result = [];
        $keep = [];

        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) { continue; }
            if ( !$_POST[ $v->name . '--uploaded' ] ) { continue; }

            foreach ( $_POST[ $v->name . '--uploaded' ] as $w ) {
                $w = sanitize_file_name( $w );
                
                $path = $dirs[ $v->name ] . '/' . $w;
                if ( !is_file( $path ) ) { continue; }

                $result[] = [ 'name' => $w, 'field' => $v->name ];
                $keep[ $path ] = true;

                if ( !$v->multiple ) { continue 2; }
            }

        }

        $this->uploaded = $result; // the list of uploaded files

        // delete files, not on the list, from server
        foreach ( $this->s->fields as $v ) {
            $dir = $dirs[ $v->name ];
            $files = array_diff( scandir( $dir ), [ '.', '..' ] );

            foreach ( $files as $file ) {
                if ( $keep[ $dir . '/' . $file ] ) { continue; }
                unlink( $dir . '/' . $file );
            }
        }
    }

    private function uploaded_files_set() {
        foreach ( $this->s->fields as $v ) {
            if ( $v->type !=='file' ) { continue; }
            $_POST[ $v->name . '--uploaded' ] = [];
        }
        foreach ( $this->uploaded as $v ) {
            $_POST[ $v['field'] . '--uploaded' ][] = $v['name'];
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
        $uploads = wp_get_upload_dir()['basedir'];
        return [
            'main' => $uploads . '/' . FCP_Forms::$tmp_dir,
            'dir' => $uploads . '/' . FCP_Forms::$tmp_dir . '/' . $_POST['fcp-form--tmpdir'],
            'base' => $_POST['fcp-form--tmpdir']
        ];
    }

}
