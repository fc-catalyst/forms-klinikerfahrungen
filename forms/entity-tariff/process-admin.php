<?php
/*
Process meta boxes data, custom $_POST filters
*/

FCP_Forms::tz_set();

$values = get_post_meta( $postID );
foreach ( $values as &$v ) { $v = $v[0]; }
require_once 'variables.php';
require_once 'mail/mail.php';


// no tariff manipulations with no billing method picked
if ( !$values['entity-billing'] && !$admin_am ) {
    $this->s->fields = [];
    return;
}

$tariff_change = $_POST['entity-tariff'] !== $values['entity-tariff'];
$pay_status_change = $_POST['entity-payment-status'] !== $values['entity-payment-status'];
// $tariff_next_change = $_POST['entity-tariff-next'] !== $values['entity-tariff-next']; // moved lower


// processing the values


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
    //FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [], 'unset' );
}


// tariff status change
if ( !$admin_am && $tariff_change && !$tariff_paid ) { // tariff is about to change to paid one by a user

    // payment status init
    $_POST['entity-payment-status'] = 'pending';
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'roles_edit', ['entity_delegate'] );

    // notify the accountant
    add_action( 'save_post', function() use ( $postID ) {
        FCP_FormsTariffMail::to_accountant( 'request', $postID );
    }, 20);
}


// tariff due date
if ( $admin_am ) { // just to not process by a user submit
    $_POST['entity-tariff-till'] = $dmy_to_dayend_timestamp( $_POST['entity-tariff-till'] );
    // the filter is lower, as the value depends on other conditions too
}


// timezones
if ( $admin_am ) {

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone', 'options', $timezones );
    
    // save the timezone with daylight saving offset
    if ( $timezones->{ $_POST['entity-timezone'] } ) {
        $zone = new DateTimeZone( $_POST['entity-timezone'] );
        $use_time = $_POST['entity-tariff-till'] ? $_POST['entity-tariff-till'] : $time;
        $_POST['entity-timezone-bias'] = $zone->getTransitions( $use_time, $use_time )[0]['offset'];
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone-bias', 'roles_edit', ['administrator'] );
    }
}


// prolong
if ( $prolong_allowed ) {

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', (object) $tariffs ); // ++

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options',
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' )
    );

    if ( !$admin_am && $tariff_paid_next ) { // only the free tariff can be changed by a user
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'roles_view', ['entity_delegate'] );
    }

    // set status for the newly picked tariff
    $tariff_next_change = $_POST['entity-tariff-next'] !== $values['entity-tariff-next'];
    
    if ( !$admin_am && $tariff_next_change && !$tariff_paid_next ) {  // tariff is about to change to a paid by a user

        $_POST['entity-payment-status-next'] = 'pending';
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'roles_edit', ['entity_delegate'] );

    }

    // notify the accountant to bill the proonging
    if ( !$admin_am && $tariff_next_change && $_POST['entity-tariff-next'] !== $tariff_default ) {
        $prolong_tariff = $_POST['entity-tariff-next'] === $values['entity-tariff']; // else - change
        add_action( 'save_post', function() use ( $postID, $prolong_tariff ) {
            FCP_FormsTariffMail::to_accountant( $prolong_tariff ? 'prolong' : 'change', $postID );
        }, 20 );
    }
}


// conditions to flush values

if ( $_POST['entity-tariff'] === $tariff_default ) {
    $_POST['entity-payment-status'] = '';
}
if ( $_POST['entity-tariff-next'] === $tariff_default ) {
    $_POST['entity-payment-status-next'] = '';
}
if ( $_POST['entity-tariff-till'] < $time ) {
    $_POST['entity-tariff-till'] = 0;
}


FCP_Forms::tz_reset();