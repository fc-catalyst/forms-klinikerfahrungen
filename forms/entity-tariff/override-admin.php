<?php
/*
Modify the values before printing to inputs
*/

FCP_Forms::tz_set(); // set utc timezone for all the time operations, in case the server has a different settings

require 'variables.php';

/*
//$cron_jobs = get_option( 'cron' );
//var_dump($cron_jobs);

FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
    'type' => 'notice',
    'meta_box' => true,
    'before' => '<pre>',
    'after' => '</pre>',
    'text' => "\n".
        print_r( _get_cron_array(), true )//date( 'd.m.Y H:i:s', 1639872000 )//print_r( _get_cron_array(), true )//print_r( $outdated->request, true )//fcp_flush_tariff_by_id( $_GET['post'] )
    ."\n",
], 'before' );
//*/


// ++--flush the tariff conditionally
//fcp_flush_tariff_by_id( $_GET['post'], $values );
//fcp_flush_dates_by_id( $_GET['post'], $values, true );
//fcp_forms_entity_tariff_prolong();

$init_values = $values;

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


// tariff requested date - is used to remind the accountant to bill in a few days after
if ( $values['entity-tariff-requested'] && $tariff_paid ) { // ++reposition, if refers to the next tariff
    $values['entity-tariff-requested'] = date( $date_format, $values['entity-tariff-requested'] + $time_bias );
} else {
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [], 'unset' );
}

// tariff billed date - is used to change unpayed paid tariffs back to free, like in a $prolong_gap period
if ( $values['entity-tariff-billed'] && $values['entity-payment-status'] === 'billed' ) { // ++reposition after next if
    $values['entity-tariff-billed'] = date( $date_format, $values['entity-tariff-billed'] );
} else {
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-billed', [], 'unset' );
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
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-timezone', 'options', $timezones );
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

//*

if ( $admin_am ) {

    if ( !$tariff_paid ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [
            'type' => 'notice',
            'text' => '<strong>The following fields effect only paid tariffs.</strong>',
            'meta_box' => true,
        ], 'after' );
    }

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

    if ( $prolong_allowed ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [
            'type' => 'notice',
            'text' => '<strong>The next tariff option is available to users '.( $prolong_gap / $day ).' days before the current <em>paid</em> tariff ends.</strong><span>If current tariff is free, you can schedule the paid one by picking a future date in the "Active till" field.</span>',
            'meta_box' => true,
        ], 'before' );
    }
    
    // a minor simplifying the interface
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'title', '', true );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'title', '', true );
}


if ( !$admin_am ) {

    // the payment status
    if ( $tariff_paid ) {

        if ( $values['entity-payment-status'] === 'pending' ) {
            $status_message = '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email <em>' . $billing_email . '</em> For any questions or problems with receiving the bill, please contact our accountant <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>';

        } elseif ( $values['entity-payment-status'] === 'billed' ) {
            $status_message = '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The initial free tariff will be restored automatically in '.floor( $billed_flush_gap / $day ).' days, if not payed.';

        }
        
        if ( $status_message ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-payment-status', [
                'type' => 'notice',
                'text' => $status_message,
                'meta_box' => true,
            ], 'override' );
            unset( $status_message );
        }

    }

    if ( $tariff_paid_next && $prolong_allowed ) {

        if ( $values['entity-payment-status-next'] === 'pending' ) {
            $status_message = '<em>Payment status - Pending: </em>You will be billed in a few days via your mentioned billing email <em>' . $billing_email . '</em> For any questions or problems with receiving the bill, please contact our accountant <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a>';

        } elseif ( $values['entity-payment-status-next'] === 'billed' ) {
            $status_message = '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The initial free tariff will be restored automatically in '.floor( $billed_flush_gap / $day ).' days, if not payed.';

        } elseif ( $values['entity-payment-status-next'] === 'payed' ) {
            $status_message = '<em>Payment status - Payed</em>';

        }
        
        if ( $status_message ) {
            FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-payment-status-next', [
                'type' => 'notice',
                'text' => $status_message,
                'meta_box' => true,
            ], 'override' );
            unset( $status_message );
        }
    }

}


//if ( $prolong_allowed && $tariff_paid && $tariff_paid_active ) {
if ( $init_values['entity-tariff-till'] ) {
    $tariff_next_start_label = date( $date_format, $init_values['entity-tariff-till'] + $day );
    $till_next = $init_values['entity-tariff-till'] - $time - $values['entity-timezone-bias'];
    if ( $till_next > $day ) {
        $till_next = round( $till_next / 60 / 60 / 24 ) . ' day(s)';
    } elseif ( $till_next <= $day && $till_next > 3600 ) {
        $till_next = round( $till_next / 60 / 60 ) . ' hour(s)';
    } elseif ( $till_next <= 3600 ) {
        $till_next = round( $till_next / 60 ) . ' minute(s)';
    }

    //$scheduled_to = date( 'd.m.Y H:i:s', wp_next_scheduled( 'fcp_forms_entity_tariff_prolong' ) );
    //$and_now_is = date( 'd.m.Y H:i:s', time() );
    //$the_event = wp_get_scheduled_event( 'fcp_forms_entity_tariff_prolong' );
    
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>The next tariff will be activated on <font color="#35b32d" style="white-space:nowrap">'.$tariff_next_start_label.'</font>, 00:00 local time, <br>in '.$till_next.'</p>',
        // <br>'.$scheduled_to.' <br>'.$and_now_is.' <br><pre>'.print_r( $the_event, true ).'</pre>
        'meta_box' => true,
    ]);
}

array_push( $this->s->fields, (object) [
    'type' => 'notice',
    'text' => '<p>For more information check out our tariff prices and conditions <a href=\"/\" target=\"_blank\">here</a></p>',
    'meta_box' => true,
    'roles_view' => ['entity_delegate'],
]);



FCP_Forms::tz_reset();