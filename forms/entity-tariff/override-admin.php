<?php
/*
Modify the values before printing to inputs
*/

// remove the not used variables after creating the saving algs

$tariff_default = 'kostenloser_eintrag'; // ++can place to json as a custom attr

$values['entity-tariff'] = $values['entity-tariff'] ? $values['entity-tariff'] : $tariff_default; // ++limit to options

$admin_am = current_user_can('administrator');
$user_trusted = get_user_by( 'ID', get_post( $_GET['post'] )->post_author )->{'user-trusted'} ? true : false;
$tariff_paid = $values['entity-tariff'] !== $tariff_default;
$tariff_running = $tariff_paid && $user_trusted // ++trust runs out separately or add && tariff runs > 2 weeks
           || $tariff_paid && $values['entity-payment-status'] === 'payed'
            ? $values['entity-tariff']
            : $tariff_default;
$prolong_available = $tariff_paid
            && $values['entity-tariff-till'] > time()
            && $values['entity-tariff-till'] - time() < 60*60*24*14;

$time_label = '';
if ( !$admin_am ) { // just not used for the admin
    if ( $values['entity-tariff-till'] - time() < 60*60*24 ) { // today
        $time_label = __( 'Ends today', 'fcpfo' );
    } elseif ( $values['entity-tariff-till'] - time() < 0 ) { // outdated
        $time_label = __( 'Not set or ended recently', 'fcpfo' );
    } else {
        $time_label = date( get_option( 'date_format' ), $values['entity-tariff-till'] );
    }
    if ( $prolong_available ) {
        $time_label = '<font color="red">' . $time_label . '</font>';
    }
}

$tariff_next_start_label = '';
if ( $prolong_available ) {
    $tariff_next_start_label = date( get_option( 'date_format' ), $values['entity-tariff-till'] + 60*60*24 );
}

$billing_details_id = get_post_meta( $_GET['post'], 'entity-billing', true );
$billing_email = get_post_meta( $billing_details_id, 'billing-email', true );


// print field-by-field conditionally


// block the tariff if no billing method picked
if ( !$billing_details_id && !$admin_am ) {
    $this->s->fields = [];
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>To apply a different tariff (to allow the <em>dofollow</em> links, for example), please select a billing details in the field above. Or fill in a new billing information <a href="https://b105qyo.myraidbox.de/wp-admin/edit.php?post_type=billing">here</a> first.</p>',
        'meta_box' => true,
        'roles_view' => ['entity_delegate'],
    ]);
    return;
}

// main tariff picker
if ( !$admin_am && $tariff_paid ) {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
}


// tariff requested date
if ( $values['entity-tariff-requested'] ) { // ++change on any tariff change (self, admin, auto)
    $values['entity-tariff-requested'] = date( get_option( 'date_format' ), $values['entity-tariff-requested'] );
} else {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-requested', 'type', 'none' );
}


// tariff due date
if ( $tariff_paid && $admin_am ) { // ++set 0 on not free tariff save or yesterday or check for upcoming tariff
    $values['entity-tariff-till'] = $values['entity-tariff-till']
        ? date( 'd.m.Y', $values['entity-tariff-till'] )
        : '';
    // ++add the link to ->after to count 1 year from now
}
if ( $tariff_paid && !$admin_am && $values['entity-payment-status'] === 'payed' ) { // just styling
    $values['entity-tariff-till'] = $time_label;
}
if ( !$tariff_paid || $tariff_paid && !$admin_am && $values['entity-payment-status'] !== 'payed' ) {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-till', 'type', 'none' );
} // ++tack empty or too small value with payed tariff to disable


// the payment status
if ( !$tariff_paid || $tariff_paid && !$admin_am && $values['entity-payment-status'] !== 'payed' ) {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'type', 'none' );
} // ++tack empty or too small value with payed tariff to disable ^^
if ( $tariff_paid && !$admin_am && $values['entity-payment-status'] ) {

    if ( $values['entity-payment-status'] === 'pending' ) {
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'type', 'notice' );
        $notice_text = '<em>Pending:</em><br>You will be billed in a few days via your mentioned billing email ' . $billing_email;
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'text', $notice_text );
    } elseif ( $values['entity-payment-status'] === 'billed' ) {
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'type', 'notice' );
        $notice_text = '<em>Billed:</em><br>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by email <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>';
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'text', $notice_text );
    } elseif ( $values['entity-payment-status'] === 'payed' ) {
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status', 'type', 'none' );
        $notice_text = '<em>Payed</em>';
    }

}


// prolong
if ( $prolong_available ) {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-next', 'type', 'notice' );
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-next', 'text', $values['entity-tariff-next'] );
    
    FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-next', 'type', 'select' );
    if ( !$values['entity-tariff-next'] ) {
        $values['entity-tariff-next'] = $tariff_default;
    }
    if ( !$admin_am && $values['entity-tariff-next'] !== $tariff_default ) {
        FCP_Forms::json_change_field( $this->s->fields, 'entity-tariff-next', 'roles_view', ['entity_delegate'] );
    }
    
    if ( $admin_am || $values['entity-payment-status-next'] && $values['entity-tariff-next'] !== $tariff_default ) {
        FCP_Forms::json_change_field( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    }
}


// helping labels
if ( $tariff_next_start_label && $admin_am ) {
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>The next tariff period will be activated <font color="red" style="white-space:nowrap">on '.$tariff_next_start_label.'</font></p>',
        'meta_box' => true,
    ]);
}

array_push( $this->s->fields, (object) [
    'type' => 'notice',
    'text' => '<p>For more information check out our tariff prices and conditions <a href=\"/\" target=\"_blank\">here</a></p>',
    'meta_box' => true,
    'roles_view' => ['entity_delegate'],
]);