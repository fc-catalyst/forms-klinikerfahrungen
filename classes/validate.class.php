<?php

/*
$_POST = [
    'calc-name' => 'calc-1-email',
    'calc' => [
        'dd@dd.',
        ''
    ]
];
$path = __DIR__ . '/../structure/'.$_POST['calc-name'].'/main.json';
$structure = @file_get_contents( $path );
//*/

class FCP_Forms__Validate {

    private $s, $v; // is for structure, json; $_POST; $_FILES (concat with $_POST here)
    public $result, $mFilesFailed; // filtered and marked content; filtered files from the list of multiples

    public function __construct($s, $v, $f = []) {

        $this->s = $s;
        $this->s->fields = self::flatten( $s->fields );
        $this->v = $v + $f;
        $this->checkValues();

    }


    private function test_name($rule, $a) {
        return self::name( $rule, $a );
    }

    private function test_notEmpty($rule, $a) {
        if ( !$rule ) {
            return false;
        }
        
        $a = is_string( $a ) ? trim( $a ) : $a;
        if ( !empty( $a ) ) {
            return false;
        }
        return 'The field is empty';
    }
    
    private function test_regExp($rule, $a) {
        if ( !$a || $a && preg_match( '/'.$rule[0].'/', $a ) ) {
            return false;
        }
        return 'Doesn\'t fit the pattern '.$rule[1];
    }

    private function test_email($rule, $a) {
        if ( !$a || $a && $rule == true && filter_var( $a, FILTER_VALIDATE_EMAIL ) ) {
            return false;
        }
        return 'The email format is incorrect';
    }
    
// __-----___---___--_________

    public static function name($rule, $a) {
        if ( !$a || $a && $rule == true && preg_match( '/^[a-zA-Z0-9-_]+$/', $a ) ) {
            return false;
        }
        return 'Must contain only letters, nubers or the following symbols: "-", "_"';
    }

    public static function flipFiles($mfile = []) {
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

    public static function flatten($f, &$return = []) {
        foreach ( $f as $add ) {

            if ( $add->type ) {
                $return[] = $add;
                continue;
            }

            if ( $add->gtype ) {
                self::flatten( $add->fields, $return );
            }

        }
        return $return;
    }

// -----____--____FILES OPERATIONS____----____---____

    private function test_file_maxSize($rule, $a) {
        if ( !$a['name'] ) {
            return false;
        }
        if ( is_numeric( $rule ) && $a['size'] < $rule ) {
            return false;
        }
        return 'The file <em>'.$a['name'].'</em> is too big. Max size is '.$rule;
    }
    
    private function test_file_extension($rule, $a) {
        if ( !$a['name'] ) {
            return false;
        }
        $ext = pathinfo( $a['name'], PATHINFO_EXTENSION );
        if ( is_array( $rule ) && in_array( $ext, $rule ) ) {
            return false;
        }
        return 'The file <em>'.$a['name'].'</em> extension doesn\'t fit the allowed list: ' . implode( ', ', $rule );
    }
    
    private function test_file_default($a) {
        if ( $a['error'] ) {
            return [ // taken from the php reference for uploading errors
                0 => 'There is no error, the file uploaded with success', // doesn't count anyways
                1 => 'The uploaded file '.$a['name'].' exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file '.$a['name'].' exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file '.$a['name'].' was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file '.$a['name'].' to disk.',
                8 => 'A PHP extension stopped the file '.$a['name'].' upload.',
            ][ $a['error'] ];
        }
        return false;
    }

// ---________---___--__________---

    private function checkValues() {

        foreach ( $this->s->fields as $f ) {

            if ( !$f->validate ) {
                continue;
            }

            foreach ( $f->validate as $mname => $rule ) {
                $method = 'test_' . ( $f->type == 'file' ? 'file_' : '' ) . $mname;
                $test = false;

                if ( !method_exists( $this, $method ) ) {
                    continue;
                }

                // multiple files
                if ( $f->type == 'file' && $f->multiple ) {
                
                    $mflip = self::flipFiles( $this->v[ $f->name ] );
                    foreach ( $mflip as $v ) {
                        if ( $this->addResult( $method, $f->name, $rule, $v ) ) {
                            $this->mFilesFailed[ $f->name ][] = $v['name'];
                        }
                    }

                    continue;
                }
                
                // text data && single file
                $this->addResult( $method, $f->name, $rule, $this->v[ $f->name ] );
            }
        }
    }
    
    private function addResult($method, $name, $rule, $a) {
        if ( $test = $this->{ $method }( $rule, $a ) ) {
            $this->result[$name][] = $test;
            return true;
        }
    }

}
