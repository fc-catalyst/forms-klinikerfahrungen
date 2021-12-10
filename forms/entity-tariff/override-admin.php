<?php
/*
Modify the values before printing to inputs
*/

FCP_Forms::tz_set(); // set utc timezone for all the time operations, in case the server has a different settings

//*

function fcp_flush_tariff_by_id($p) {
    if ( !$p ) { return; }
    if ( is_array( $p ) ) { $p = (object) $p; }
    if ( is_object( $p ) && !$p->ID ) { return; }
    if ( is_numeric( $p ) ) {
        $p = (object) [
            'ID' => $p
        ];
    }
    $p->ID = (int) $p->ID; // intval()
    
    $a2q = function($arr = null) {
        static $arr_saved = [];
        if ( !$arr ) { return $arr_saved; }
        $arr_saved = $arr;
        if ( !$arr[0] ) return '1=1'; // pick all fields if no elements
        return '`meta_key` = %s' . str_repeat( ' OR `meta_key` = %s', count( $arr ) - 1 );
    };
    
    // get more values if are not provided, else - trust and do what has to be done
    if ( !isset( $p->till ) || !isset( $p->tariff_next ) || !isset( $p->status_next ) ) {
        global $wpdb;
        
        $q = $a2q( ['entity-tariff', 'entity-tariff-till', 'entity-timezone-bias', 'entity-tariff-next', 'entity-payment-status-next'] ); //++remove unused
        
        $query = 'SELECT `meta_key`, `meta_value` FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( '.$q.' )';
        $query = $wpdb->prepare( $query, array_merge( [ $p->ID ], $a2q() ) );
        if ( $query === null ) { return; }
        
        $results = $wpdb->get_results( $query );
        foreach ( $results as $v ) { $p->{ $v->meta_key } = $v->meta_value; }
        unset( $results, $q, $query, $v );

        // check if really outdated
        $p->{ 'entity-timezone-bias' } = $p->{ 'entity-timezone-bias' } ? (int) $p->{ 'entity-timezone-bias' } : 0;
        if ( (int) $p->{ 'entity-tariff-till' } - $p->{ 'entity-timezone-bias' } < time() ) { return; }
        
        $p->till = $p->{ 'entity-tariff-till' };
        $p->tariff_next = $p->{ 'entity-tariff-next' };
        $p->status_next = $p->{ 'entity-payment-status-next' };
        //++unset not used
        
    }
    return;

    // remove outdated meta
    $q = $a2q( ['entity-tariff', 'entity-payment-status', 'entity-tariff-till', 'entity-tariff-next', 'entity-payment-status-next'] );
    $query = 'DELETE FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( '.$q.' )';
    if ( $query = $wpdb->prepare( $query, array_merge( [ $p->ID ], $a2q() ) ) ) { $wpdb->query( $query ); }
    
    // insert new data - do the insert data
    // entity-tariff if is next
    // status if is next
    // entity-tariff-till = till++
    // ??can update timezone bias, but Y, but would be correct, if previous period was not 1 year - maybe pick it form the query!!
    // ++ find and avoid situation, where no tarif is set - where can it be?
    $wpdb->query( '
        INSERT INTO `'.$wpdb->postmeta.'` ( `post_id`, `meta_key`, `meta_value` ) VALUES ( "'.$v->ID.'", "entity-tariff-requested", "'.( $v->till + 1 ).'" )
    ');
/*    
    if ( $v->tariff_next ) {
        $wpdb->query( '
            DELETE FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "entity-tariff" AND `post_id` = "'.$v->ID.'"
        ');
        $wpdb->query( '
            UPDATE `'.$wpdb->postmeta.'` SET `meta_key` = "entity-tariff" WHERE `meta_key` = "entity-tariff-next" AND `post_id` = "'.$v->ID.'"
        ');
    }
    if ( $v->status_next ) {
        $wpdb->query( '
            DELETE FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "entity-payment-status" AND `post_id` = "'.$v->ID.'"
        ');
        $wpdb->query( '
            UPDATE `'.$wpdb->postmeta.'` SET `meta_key` = "entity-payment-status" WHERE `meta_key` = "entity-payment-status-next" AND `post_id` = "'.$v->ID.'"
        ');
    }
    
    // replace the tariff-requested date
    $wpdb->query( '
        DELETE FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "entity-tariff-requested" AND `post_id` = "'.$v->ID.'"
    ');
    $wpdb->query( '
        INSERT INTO `'.$wpdb->postmeta.'` ( `post_id`, `meta_key`, `meta_value` ) VALUES ( "'.$v->ID.'", "entity-tariff-requested", "'.( $v->till + 1 ).'" )
    ');

    // replace the tariff-till date
    $wpdb->query( '
        DELETE FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "entity-tariff-till" AND `post_id` = "'.$v->ID.'"
    ');
    $wpdb->query( '
        INSERT INTO `'.$wpdb->postmeta.'` ( `post_id`, `meta_key`, `meta_value` ) VALUES ( "'.$v->ID.'", "entity-tariff-till", "'.strtotime( '+1 year', $v->till ).'" )
    ');

//*/
    return print_r( $p, true );
    // ++ return new $values, that were changed by the function
}
// ++function to flush the billed status, like every day?
// ++function to flush the requested date

FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
    'type' => 'notice',
    'meta_box' => true,
    'before' => '<pre>',
    'after' => '</pre>',
    'text' => "\n".
        fcp_flush_tariff_by_id( $_GET['post'] )
    ."\n",
], 'before' );
//*/


include 'variables.php';

// no tariff manipulations with no billing method picked
if ( !get_post_meta( $_GET['post'], 'entity-billing', true ) && !$admin_am ) {
    $this->s->fields = [];
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>To apply a different tariff, please select a billing details in the field above. Or fill in a new billing information <a href="/wp-admin/post-new.php?post_type=billing" target="_blank">here</a> first.</p>',
        'meta_box' => true,
    ]);
    return;
}

// meeting the reset / update conditions
/*
if ( $values['entity-tariff-till'] <= $time_local ) {
    // +++reset the tariff to free or apply the next one ++ move to top ()
}
+++ collect other demanded further flushes here
//*/

// print field-by-field conditionally


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
}


// tariff requested date - is used to change unpayed paid tariffs back to free, like in a $prolong_gap period
if ( $values['entity-tariff-requested'] ) {
    $values['entity-tariff-requested'] = date( $date_format, $values['entity-tariff-requested'] + $time_bias );
}
if ( !$values['entity-tariff-requested'] ) { // ++add reset conditions
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [], 'unset' );
}


// tariff due date
if ( $admin_am ) { // format for the input
    $values['entity-tariff-till'] = $values['entity-tariff-till'] > $time_local
        ? date( 'd.m.Y', $values['entity-tariff-till'] )
        : '';

} else {

    if ( $tariff_paid && $values['entity-payment-status'] === 'payed' ) {
        // human readable format & styling; can just comment if too complex
        $values['entity-tariff-till'] = $time_label( $values['entity-tariff-till'], $tariff_ends_in < $prolong_gap );
    } else {
        // hide
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-till', [], 'unset' );
    }
}


// timezones
if ( $admin_am ) { // ++allow users to change zones before payed in future, when not one country coverage
    // make the list of timezones
    $tzs = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
    $tzs = array_combine( $tzs, $tzs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone', 'options', (object) $tzs );
}


// prolong
if ( $prolong_allowed ) {

    // activate and pre-fill the -next fields
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', $tariffs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'value', $tariff_default );

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options',
        (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' ) // ++
    );

    if ( !$admin_am && $tariff_paid_next ) { // only the free tariff can be changed by a user
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'roles_view', ['entity_delegate'] );
    }
}



// helping text labels

/*

if ( $prolong_allowed ) {
    if ( $admin_am ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [
            'type' => 'notice',
            'text' => '<strong>The following fields are available to users '.( $prolong_gap / ($day) ).' days before a paid tariff ends.</strong>',
            'meta_box' => true,
        ], 'before' );
    }

    if ( !$admin_am && $tariff_paid_next ) {

        if ( $values['entity-payment-status-next'] === 'pending' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email ' . $billing_email . '. Contact our accountant, if you have problem with receiving the bill <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                    'meta_box' => true,
                ],
                'override'
            );

        } elseif ( $values['entity-payment-status-next'] === 'billed' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                    'meta_box' => true,
                ],
                'override'
            );

        } elseif ( $values['entity-payment-status-next'] === 'payed' ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields,
                'entity-payment-status-next',
                [
                    'type' => 'notice',
                    'text' => '<em>Payment status - Payed</em>',
                    'meta_box' => true,
                ],
                'override'
            );

        }

    }

}


// the payment status
if ( !$admin_am && $tariff_paid ) { // ++add reset conditions

    if ( $values['entity-payment-status'] === 'pending' ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields,
            'entity-payment-status',
            [
                'type' => 'notice',
                'text' => '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email ' . $billing_email . '. Contact our accountant, if you have problem with receiving the bill <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>',
                'meta_box' => true,
            ],
            'override'
        );

    } elseif ( $values['entity-payment-status'] === 'billed' ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields,
            'entity-payment-status',
            [
                'type' => 'notice',
                'text' => '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The tariff will be activated when the payment is received. If not payed in 2 weeks, the initial free tariff will be restored.',
                'meta_box' => true,
            ],
            'override'
        );

    }

}





// helping labels

if ( $prolong_allowed ) {
    $tariff_next_start_label = $values['entity-tariff-till'];//date( get_option( 'date_format' ), $values['entity-tariff-till'] + $day );
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>The next tariff period will be activated <font color="#b32d2e" style="white-space:nowrap">on '.$tariff_next_start_label.'</font></p>',
        'meta_box' => true,
    ]);
}

array_push( $this->s->fields, (object) [
    'type' => 'notice',
    'text' => '<p>For more information check out our tariff prices and conditions <a href=\"/\" target=\"_blank\">here</a></p>',
    'meta_box' => true,
    'roles_view' => ['entity_delegate'],
]);

if ( $admin_am && !$tariff_paid ) { // just a notice
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
        'type' => 'notice',
        'text' => '<strong>The following fields will not be effecting a free tariff.</strong>',
        'meta_box' => true,
    ], 'after' );
}


// tariff due date
if ( $admin_am ) { // ++add reset conditions

    // date picker helping functions
    $one_year_from_now_plus_one_day = date( 'd.m.Y', strtotime( '+1 year', $time_local + $day ) );
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-till', [
        'type' => 'notice',
        'text' => '<a href="#" id="one-year-ahead" style="margin-top:-12px">Set 1 year from now</a><script>
            jQuery( \'#one-year-ahead\' ).click( function( e ) {
                e.preventDefault();
                jQuery( \'#entity-tariff-till_entity-tariff\' ).val( \'' . $one_year_from_now_plus_one_day . '\' );
            });
        </script>',
        'meta_box' => true,
    ], 'after' );

}
//*/

FCP_Forms::tz_reset();