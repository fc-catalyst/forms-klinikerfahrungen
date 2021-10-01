<?php
/*
Process meta boxes data
*/

// custom $_POST filters

$_POST['entity-tariff-till'] = trim( $_POST['entity-tariff-till'] );

if (
    !$_POST['entity-tariff-till'] ||
    !preg_match( '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $_POST['entity-tariff-till'] )
) { return; }


$d = DateTime::createFromFormat( 'd.m.y', $_POST['entity-tariff-till'] );
if ( $d === false ) {
    $d = DateTime::createFromFormat( 'd.m.Y', $_POST['entity-tariff-till'] );
}
if ( $d === false ) { return; }

$_POST['entity-tariff-till'] = $d->getTimestamp();


