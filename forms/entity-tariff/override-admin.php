<?php
/*
Modify the values before printing to inputs
*/

if ( current_user_can('administrator') ) {
    if ( !$values['entity-tariff-till'] ) {
        $values['entity-tariff-till'] = '';
        return;
    }
    $values['entity-tariff-till'] = date( 'd.m.Y', $values['entity-tariff-till'] );
    return;
}

if ( $values['entity-tariff-till'] ) {

    if ( $values['entity-tariff-till'] < time() ) {
        if ( time() - $values['entity-tariff-till'] < 60*60*24 ) {
            $values['entity-tariff-till'] = 'Ends today';
            return;
        }
        $values['entity-tariff-till'] = 'Not set or ended recently';
        return;
    }

    $values['entity-tariff-till'] = date( get_option( 'date_format' ), $values['entity-tariff-till'] );
    return;
}

$values['entity-tariff-till'] = 'Not limited'; // 0