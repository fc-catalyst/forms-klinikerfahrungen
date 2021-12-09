<?php
/*
Process meta boxes data, custom $_POST filters
*/

FCP_Forms::tz_set();

// initial values

$time = time();
$time_local = $time + ( $values['entity-timezone-bias'] ? $values['entity-timezone-bias'] : 0 ); // the saved one
$day = 60*60*24;
$prolong_gap = $day*30;

$values = get_post_meta( $postID ); // ++or use the fct1_meta
foreach ( $values as &$v ) { $v = $v[0]; }

// variables //++ unify the part with variables for override and process files


$tariffs = (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'options' );
$tariff_default = FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'value' );

$tariff_paid = $values['entity-tariff'] !== $tariff_default;

$till_limit = $values['entity-tariff-till'] - $time_local;

$admin_am = current_user_can( 'administrator' );

$prolong_available = $tariff_paid && $till_limit > 0 && ( $till_limit < $prolong_gap || $admin_am );

$values['entity-tariff'] = $values['entity-tariff'] && $tariffs[ $values['entity-tariff'] ]
                         ? $values['entity-tariff']
                         : $tariff_default;



// processing the values



// block the tariff if no billing method picked
if ( !$values['entity-billing'] && !$admin_am ) {
    // ++add warning?
    $this->s->fields = [];
}


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [], 'unset' );
}


// payed tariff is requested by a user // ++add reset conditions to the scheduler
if ( !$admin_am && !$tariff_paid && $_POST['entity-tariff'] !== $values['entity-tariff'] ) {

    // requested date
    $_POST['entity-tariff-requested'] = $time;
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [
        'type' => 'text',
        'name' => 'entity-tariff-requested',
        'meta_box' => true,
    ], 'override' );
    
    // payment status init
    $_POST['entity-payment-status'] = 'pending';
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'roles_edit', ['entity_delegate'] );
    
    // +++ send mail to admin here, that paid tariff is requested by an user
}


// prolong statements

if ( $prolong_available ) {

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', (object) $tariffs );

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    $pay_statuses = FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options', $pay_statuses );


    $values['entity-tariff-next'] = $values['entity-tariff-next'] && $tariffs[ $values['entity-tariff-next'] ]
                                ? $values['entity-tariff-next']
                                : $tariff_default;
    $tariff_paid_next = $values['entity-tariff-next'] !== $tariff_default;
    if ( !$admin_am && $tariff_paid_next ) { // don't allow changing a paid tariff
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [], 'unset' );
    }

    // set status for the newly picked tariff
    if ( !$admin_am && !$tariff_paid_next && $_POST['entity-tariff-next'] !== $values['entity-tariff-next'] ) {
        $_POST['entity-payment-status-next'] = 'pending';
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'roles_edit', ['entity_delegate'] );
    }
}


// date to timestamp of the end of the day
if ( $admin_am ) {

    $_POST['entity-tariff-till'] = trim( $_POST['entity-tariff-till'] );

    if (
        !$_POST['entity-tariff-till'] ||
        !preg_match( '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $_POST['entity-tariff-till'] )
    ) {
        $_POST['entity-tariff-till'] = 0;
    }


    if ( $_POST['entity-tariff-till'] ) {

        $d = DateTime::createFromFormat( 'd.m.y H:i:s', $_POST['entity-tariff-till'] . ' 23:59:59', new DateTimeZone( 'UTC' ) );
        if ( $d === false ) {
            $d = DateTime::createFromFormat( 'd.m.Y H:i:s', $_POST['entity-tariff-till'].' 23:59:59', new DateTimeZone( 'UTC' ) );
        }
        if ( $d !== false ) {
            $_POST['entity-tariff-till'] = $d->getTimestamp();
        }

    }

}


// timezones - when an admin marks the tariff as payed
if ( $admin_am && $_POST['entity-payment-status'] === 'payed' && $_POST['entity-payment-status'] !== $values['entity-payment-status'] ) {
    $tzs = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
    $tzs = array_combine( $tzs, $tzs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone', 'options', (object) $tzs );
    
    // save the bias with daylight saving offset
    if ( $tzs[ $_POST['entity-timezone'] ] ) {
        $zone = new DateTimeZone( $_POST['entity-timezone'] );
        $_POST['entity-timezone-bias'] = $zone->getTransitions( time(), time() )[0]['offset'];
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-timezone-bias', [
            'type' => 'text',
            'name' => 'entity-timezone-bias',
            'meta_box' => true,
        ], 'override' );
    }
}


// flush the values conditions
if ( $admin_am ) {
    if ( $_POST['entity-tariff-till'] && $_POST['entity-tariff-till'] < $time_local ||
         $_POST['entity-tariff'] === $tariff_default
    ) {
    
        $_POST['entity-tariff-till'] = 0;
        $_POST['entity-tariff'] = $tariff_default;
        $_POST['entity-payment-status'] = '';
        $_POST['entity-tariff-requested'] = 0;
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [
            'type' => 'text',
            'name' => 'entity-tariff-requested',
            'meta_box' => true,
        ], 'override' );

    }
    
    if ( $_POST['entity-tariff-next'] === $tariff_default ) {
        $_POST['entity-payment-status-next'] = '';
    }
}

FCP_Forms::tz_reset();