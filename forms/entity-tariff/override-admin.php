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
            $values['entity-tariff-till'] = __( 'Ends today', 'fcpfo' );
            return;
        }
        $values['entity-tariff-till'] = __( 'Not set or ended recently', 'fcpfo' );
        return;
    }

    $values['entity-tariff-till'] = date( get_option( 'date_format' ), $values['entity-tariff-till'] );
    return;
}

$values['entity-tariff-till'] = __( 'Not limited', 'fcpfo' ); // 0