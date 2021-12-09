<?php
/*
Modify the values before printing to inputs
*/

// variables
//error_log( date( 'Y-m-d H:i:s ', mktime( 0, 0, 0, date( 'n' ), date( 'j' ), date( 'Y' ) ) ) . date_default_timezone_get() );
/*
    $zone = new DateTimeZone( 'Europe/Berlin' );
    $zone1 = new DateTimeZone("Asia/Taipei");
    //$zone2 = new DateTimeZone("Europe/Moskow");
    //$zone3 = new DateTimeZone("Europe/Helsinki");
    $offset = $zone->getOffset( new DateTime( 'now' ) ); // UTC
    echo ' ' . $zone->getOffset( new DateTime( 'now', $zone1 ) );
    //echo ' ' . $zone->getOffset( new DateTime( 'now', $zone2 ) );
    //echo ' ' . $zone->getOffset( new DateTime( 'now', $zone3 ) );
    
    echo '<br>' . $offset;
    // print utc, berlin, helsinki, moskow 
    //https://www.php.net/manual/ru/class.datetimezone.php
    //https://www.php.net/manual/ru/datetimezone.getoffset.php
    
    exit;
//*/

//https://wordpress.stackexchange.com/questions/237957/get-post-with-multiple-meta-keys-and-value

global $wpdb; // AND entity-timezone =  AND ( `entity-tariff` != "kostenloser_eintrag" and is tariff )  AND `meta_value` < "'. time() + timezone*60*60 . '" CAST(Value AS UNSIGNED) Daylight Savings
/*
$to_update = $wpdb->get_results( '
    SELECT `post_id`
    FROM `'.$wpdb->postmeta.'` AS `meta1`
    WHERE `meta_key` = "entity-tariff-till" AND `meta_value` < ' . time() . ' AND `meta_value` > 0
    ORDER BY `post_id` ASC
');
//*/
//*
$to_update = $wpdb->get_results( '
    SELECT `m1`.`post_id`, `m1`.`meta_value` AS `tariff`, `m2`.`meta_value` AS `till`
    FROM `'.$wpdb->postmeta.'` AS `m1`
    JOIN `'.$wpdb->postmeta.'` AS `m2`
        ON ( `m1`.`post_id` = `m2`.`post_id` )
    WHERE `m1`.`meta_key` = "entity-tariff" AND `m1`.`meta_value` != "kostenloser_eintrag" AND `m1`.`meta_value` != ""
        AND `m2`.`meta_key` = "entity-tariff-till" AND `m2`.`meta_value` < ' . time() . ' AND `m2`.`meta_value` > 0
    ORDER BY `post_id` ASC
');
//*/
/*
$args = array(
    'post_type'  => ['clinic', 'doctor'],
    'meta_query' => array(
        array(
            'key'     => 'entity-tariff',
            'value'   => 'premiumeintrag',
        ),
        array(
            'key'     => 'entity-tariff-till',
            'compare' => 'NOT EXISTS',
        ),
    ),
);
$to_update = new WP_Query( $args );
//*/

/*
SELECT SQL_CALC_FOUND_ROWS wpfcp_posts.ID FROM wpfcp_posts  INNER JOIN wpfcp_postmeta ON ( wpfcp_posts.ID = wpfcp_postmeta.post_id )  INNER JOIN wpfcp_postmeta AS mt1 ON ( wpfcp_posts.ID = mt1.post_id ) WHERE 1=1  AND ( 
  ( wpfcp_postmeta.meta_key = 'entity-tariff' AND wpfcp_postmeta.meta_value = 'premiumeintrag' ) 
  AND 
  mt1.meta_key = 'entity-tariff-till'
) AND wpfcp_posts.post_type IN ('clinic', 'doctor') AND (wpfcp_posts.post_status = 'publish' OR wpfcp_posts.post_status = 'dp-rewrite-republish' OR wpfcp_posts.post_status = 'future' OR wpfcp_posts.post_status = 'draft' OR wpfcp_posts.post_status = 'pending' OR wpfcp_posts.post_author = 2 AND wpfcp_posts.post_status = 'private') GROUP BY wpfcp_posts.ID ORDER BY wpfcp_posts.post_date DESC LIMIT 0, 12
//*/

$zone = new DateTimeZone( 'Europe/Berlin' );
$time = time();//1632347713;//time();// + 60 * 60 * 24 * 30 * 6;
$offset = $zone->getTransitions( $time, $time )[0]['offset']; // with Daylight Savings offset
//$offset = $zone->getOffset( new DateTime( 'now' ) ); // with NO Daylight Savings offset
//'entity-tariff-till-offset' = $offset / 360

FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
    'type' => 'notice',
    'meta_box' => true,
    'before' => '<pre>',
    'after' => '</pre>',
    'text' => "\n"
        . "\n"
        . date( 'Y-m-d H:i:s ', $time )
        . "\n"
        . date( 'Y-m-d H:i:s ', $time + $offset )
        . "\n"
        . date( 'Y-m-d H:i:s', mktime( 0, 0, 0, date( 'n' ), date( 'j' ), date( 'Y' ) ) )
        . "\n"
        . date( 'Y-m-d H:i:s', mktime( 0, 0, 0, date( 'n' ), date( 'j' ), date( 'Y' ) ) + $offset )
        . "\n"
        . ( 1632347713 < time() + $offset )
        . "\n\n"
        .  print_r( $to_update, true ),
], 'before' );


// ++flush the date on submit if tariff is free

$prolong_gap = 60*60*24*14;

$tariffs = (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'options' );
$tariff_default = FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'value' );

$values['entity-tariff'] = $values['entity-tariff'] && $tariffs[ $values['entity-tariff'] ]
                         ? $values['entity-tariff']
                         : $tariff_default;

$tariff_paid = $values['entity-tariff'] !== $tariff_default;

$admin_am = current_user_can( 'administrator' );

$values['entity-tariff-till'] = $values['entity-tariff-till'] ? $values['entity-tariff-till'] : 0;
$time_gap = $values['entity-tariff-till'] - time();
$tariff_till_view = date( get_option( 'date_format' ), $values['entity-tariff-till'] );

if ( $time_gap < 0 ) { // outdated
    $time_label = sprintf( __( 'Ended on %s', 'fcpfo' ), $tariff_till_view );
} elseif ( $time_gap < 60*60*24 ) { // today
    $time_label = __( 'Ends today', 'fcpfo' );
} elseif ( $time_gap < 60*60*24*2 ) { // tomorrow
    $time_label = __( 'Tomorrow is the last day', 'fcpfo' );
} else {
    $time_label = $tariff_till_view;
}

if ( $values['entity-tariff-till'] === 0 ) {
    $time_label = __( 'Not set', 'fcpfo' );
}

// prolong variables

// the prolong is available to users 2 weeks before the current paid tariff ends
$prolong_available = $tariff_paid && $time_gap > 0 && ( $time_gap < $prolong_gap || $admin_am );


if ( $prolong_available ) {

    $time_label = $time_gap < $prolong_gap ? '<font color="#b32d2e">' . $time_label . '</font>' : '';
    
    $values['entity-tariff-next'] = $values['entity-tariff-next'] && $tariffs[ $values['entity-tariff-next'] ]
                                ? $values['entity-tariff-next']
                                : $tariff_default;

    $tariff_paid_next = $values['entity-tariff-next'] !== $tariff_default;
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
        'text' => '<p>To apply a different tariff, please select a billing details in the field above. Or fill in a new billing information <a href="/wp-admin/post-new.php?post_type=billing" target="_blank">here</a> first.</p>',
        'meta_box' => true,
    ]);
    return;
}


// main tariff picker
if ( !$admin_am && $tariff_paid ) { // only the free tariff can be changed by a user
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff', 'roles_view', ['entity_delegate'] );
}
if ( $admin_am && !$tariff_paid ) { // just a notice
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
        'type' => 'notice',
        'text' => '<strong>The following fields will not be effecting a free tariff.</strong>',
        'meta_box' => true,
    ], 'after' );
}


// tariff requested date - used to change unpayed paid tariffs back to free, like in 2 weeks
if ( $values['entity-tariff-requested'] ) {
    $values['entity-tariff-requested'] = date( get_option( 'date_format' ), $values['entity-tariff-requested'] );
}
if ( !$values['entity-tariff-requested'] ) { // ++add reset conditions
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-requested', [], 'unset' );
}


// tariff due date
if ( $admin_am ) { // ++add reset conditions

    $values['entity-tariff-till'] = $values['entity-tariff-till'] && $values['entity-tariff-till'] > time()
        ? date( 'd.m.Y', $values['entity-tariff-till'] )
        : '';

    // date picker helping functions
    $one_year_from_now_plus_one_day = date( 'd.m.Y', strtotime( '+1 year', time() + 60*60*24 ) );
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
if ( !$admin_am && $tariff_paid && $values['entity-payment-status'] === 'payed' ) { // just styling
    $values['entity-tariff-till'] = $time_label;
}
if ( !$admin_am && $values['entity-payment-status'] !== 'payed' ) { // hide payed till date
    FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-till', [], 'unset' );
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


// prolong

if ( $prolong_available ) {

    // prolong tariff picker
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'type', 'select' ); // ++add reset conditions
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'options', $tariffs );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'value', $tariff_default );

    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'type', 'select' );
    $pay_statuses = (array) FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status', 'options' );
    FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-payment-status-next', 'options', $pay_statuses );


    if ( $admin_am ) {
        FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff-next', [
            'type' => 'notice',
            'text' => '<strong>The following fields are available to users '.( $prolong_gap / (60*60*24) ).' days before a paid tariff ends.</strong>',
            'meta_box' => true,
        ], 'before' );
    }

    if ( !$admin_am && $tariff_paid_next ) {

        // allow changing tariff only from free to a paid one
        FCP_Forms::json_attr_by_name( $this->s->fields, 'entity-tariff-next', 'roles_view', ['entity_delegate'] );

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



// helping labels
if ( $tariff_next_start_label ) {
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