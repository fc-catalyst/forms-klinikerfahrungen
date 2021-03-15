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

    private $s, $v; // is for structure, json
    public $result; // filtered and marked content

    public function __construct($s, $v) {
        $this->s = $s;
        $this->v = $v;
        $this->result = $this->checkValues();
    }


    private function test_name($rule, $a) {
        if ( $rule == true && preg_match( '/^[a-zA-Z0-9-_]+$/', $a ) ) {
            return false;
        }
        return 'Must contain only letters, nubers or the following symbols: "-", "_"';
    }

    private function test_notEmpty($rule, $a) {
        $a = trim( $a );
        if ( $rule == true && $a != '' ) {
            return false;
        }
        return 'The field is empty';
    }
    
    function test_regExp($rule, $a) {
        if ( preg_match( '/'.$rule[0].'/', $a ) ) {
            return false;
        }
        return 'Doesn\'t fit the pattern '.$rule[1];
    }

    function test_email($rule, $a) {
        if ( $rule == true && filter_var( $a, FILTER_VALIDATE_EMAIL ) ) {
            return false;
        }
        return 'The email format is incorrect';
    }
    
// __-----___---___--_________

    public static function name($rule, $a) { // the static copy of test_name
        if ( $rule == true && preg_match( '/^[a-zA-Z0-9-_]+$/', $a ) ) {
            return false;
        }
        return 'Must contain only letters, nubers or the following symbols: "-", "_"';
    }
    
// ---________---___--__________---

    private function checkValues() {
        $result = [];

        foreach ( $this->s->fields as $f ) {

            if ( !$f->validate ) {
                continue;
            }

            foreach ( $f->validate as $mname => $rule ) {
                $method = 'test_' . $mname;
                $test = false;

                if ( method_exists( $this, $method ) && $test = $this->{ $method }( $rule, $this->v[ $f->name ] ) ) {
                    $result[ $f->name ][] = $test;
                }
            }
        }
        
        return $result;
    }
    
}
