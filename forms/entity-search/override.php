<?php
/*
Print something else instead of the form
*/

if ( !$_GET['specialty'] && !$_GET['place'] ) { return; }

foreach ( $json->fields as $k => $v ) { 
    if ( $v->name == 'specialty' && $_GET['specialty'] ) {
        $json->fields[$k]->value = htmlspecialchars( urldecode( $_GET['specialty'] ) );
    }
    if ( $v->name == 'place' && $_GET['place'] ) {
        $json->fields[$k]->value = htmlspecialchars( urldecode( $_GET['place'] ) );
    }
}
