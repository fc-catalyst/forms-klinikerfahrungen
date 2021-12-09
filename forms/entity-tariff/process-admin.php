<?php
/*
Process meta boxes data, custom $_POST filters
*/

FCP_Forms::tz_set();

$values = get_post_meta( $postID );
foreach ( $values as &$v ) { $v = $v[0]; }
include 'variables.php';

// no tariff manipulations with no billing method picked
if ( !$values['entity-billing'] && !$admin_am ) {
    $this->s->fields = [];
    return;
}

$tariff_change = $_POST['entity-tariff'] !== $values['entity-tariff'];

// processing the values


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
    //FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [], 'unset' );
}


// tariff requested date // ++add reset conditions here and to the scheduler
if ( !$admin_am && !$tariff_paid && $tariff_change ) { // tariff is about to change to paid by a user

    // payment status init
    $_POST['entity-payment-status'] = 'pending';
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'roles_edit', ['entity_delegate'] );

    // requested date save
    $_POST['entity-tariff-requested'] = $time;
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-requested', 'roles_view', '', 'unset' );

    // +++ send mail to admin here, that paid tariff is requested by an user
}


// tariff due date
if ( $admin_am ) {
    $_POST['entity-tariff-till'] = $dmy_to_dayend_timestamp( $_POST['entity-tariff-till'] );
    // the filter is lower, as the value depends on other conditions too
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


// prolong statements

if ( $prolong_allowed ) {

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', (object) $tariffs );

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    $pay_statuses = FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options', $pay_statuses );

    if ( !$admin_am && $tariff_paid_next ) { // don't allow changing a paid tariff
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [], 'unset' );
    }

    // set status for the newly picked tariff
    if ( !$admin_am && !$tariff_paid_next && $_POST['entity-tariff-next'] !== $values['entity-tariff-next'] ) {
        $_POST['entity-payment-status-next'] = 'pending';
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'roles_edit', ['entity_delegate'] );
    }
}

// flush the values conditions
if ( $admin_am ) {
    if ( $_POST['entity-tariff-till'] && $_POST['entity-tariff-till'] < $time_local ||
         $_POST['entity-tariff'] === $tariff_default
    ) {
    //++ also flush if status is not payed??
    
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