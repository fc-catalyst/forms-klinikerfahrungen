<?php
/*
Process meta boxes data
*/

// custom $_POST filters

if ( !current_user_can('administrator') ) { return; }

$_POST['entity-tariff-till'] = trim( $_POST['entity-tariff-till'] );

if (
    !$_POST['entity-tariff-till'] ||
    !preg_match( '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $_POST['entity-tariff-till'] )
) {
    $_POST['entity-tariff-till'] = 0;
    return;
}


$d = DateTime::createFromFormat( 'd.m.y H:i:s', $_POST['entity-tariff-till'] . ' 23:59:59' );
if ( $d === false ) {
    $d = DateTime::createFromFormat( 'd.m.Y H:i:s', $_POST['entity-tariff-till'] . ' 23:59:59' );
}
if ( $d === false ) { return; }

$_POST['entity-tariff-till'] = $d->getTimestamp();


