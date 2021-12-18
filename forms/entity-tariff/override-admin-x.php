<?php
/*
Modify the values before printing to inputs
*/

FCP_Forms::tz_set(); // set utc timezone for all the time operations, in case the server has a different settings

/*

function fcp_flush_tariff_by_id($p) { // ++add the timezone here and dont exclude the free ones if are
    if ( !$p ) { return; }
    if ( is_array( $p ) ) { $p = (object) $p; }
    if ( is_object( $p ) && !$p->ID ) { return; }
    if ( is_numeric( $p ) ) {
        $p = (object) [
            'ID' => $p
        ];
    }
    $p->ID = (int) $p->ID; // intval()
    
    $a2q = function($arr = null) { // ++send to a separate class for the form?
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


    return print_r( $p, true );
    // ++ return new $values, that were changed by the function
}
// ++function to flush the billed status, like every day?
// ++function to flush the requested date

SELECT posts.ID,
    mt0.meta_value AS till, #entity-tariff-till
    IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next,
    IF ( mt6.meta_key = "entity-payment-status-next", mt6.meta_value, NULL ) AS status_next,
    IF ( mt8.meta_key = "entity-timezone", mt8.meta_value, NULL ) AS timezone
  FROM `wpfcp_posts` AS posts
  LEFT JOIN `wpfcp_postmeta` AS mt0 ON ( posts.ID = mt0.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt1 ON ( posts.ID = mt1.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt2 ON ( posts.ID = mt2.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt3 ON ( posts.ID = mt3.post_id AND mt3.meta_key = "entity-timezone-bias" )
  LEFT JOIN `wpfcp_postmeta` AS mt4 ON ( posts.ID = mt4.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt5 ON ( posts.ID = mt5.post_id AND mt5.meta_key = "entity-tariff-next" )
  LEFT JOIN `wpfcp_postmeta` AS mt6 ON ( posts.ID = mt6.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt7 ON ( posts.ID = mt7.post_id AND mt7.meta_key = "entity-payment-status-next" )
  LEFT JOIN `wpfcp_postmeta` AS mt8 ON ( posts.ID = mt8.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt9 ON ( posts.ID = mt9.post_id AND mt9.meta_key = "entity-timezone" )
WHERE 1 = 1 AND (
  ( mt0.meta_key = "entity-tariff-till" AND mt1.meta_value != "0" )
  AND
  ( mt1.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < 1639572503 )
  AND
  ( mt2.meta_key = "entity-timezone-bias" OR mt3.post_id IS NULL )
  AND
  ( mt4.meta_key = "entity-tariff-next" OR mt5.post_id IS NULL )
  AND
  ( mt6.meta_key = "entity-payment-status-next" OR mt7.post_id IS NULL )
  AND
  ( mt8.meta_key = "entity-timezone" OR mt9.post_id IS NULL )
) AND posts.post_type IN ("clinic", "doctor") GROUP BY posts.ID

//*/
/*
    global $wpdb;
    // ID, till_local, tariff_next, status_next
    $outdated = '
SELECT posts.ID,
    mt0.meta_value AS till,
    IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next,
    IF ( mt6.meta_key = "entity-payment-status-next", mt6.meta_value, NULL ) AS status_next
  FROM `'.$wpdb->posts.'` AS posts
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt0 ON ( posts.ID = mt0.post_id )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt1 ON ( posts.ID = mt1.post_id )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt2 ON ( posts.ID = mt2.post_id )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt3 ON ( posts.ID = mt3.post_id AND mt3.meta_key = "entity-timezone-bias" )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt4 ON ( posts.ID = mt4.post_id )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt5 ON ( posts.ID = mt5.post_id AND mt5.meta_key = "entity-tariff-next" )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt6 ON ( posts.ID = mt6.post_id )
  LEFT JOIN `'.$wpdb->postmeta.'` AS mt7 ON ( posts.ID = mt7.post_id AND mt7.meta_key = "entity-payment-status-next" )
WHERE 1 = 1 AND (
  ( mt0.meta_key = "entity-tariff-till" AND mt1.meta_value != "0" ) #can be removed? as the whole row is removed on 0
  AND
  ( mt1.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < '.time().' )
  AND
  ( mt2.meta_key = "entity-timezone-bias" OR mt3.post_id IS NULL )
  AND
  ( mt4.meta_key = "entity-tariff-next" OR mt5.post_id IS NULL )
  AND
  ( mt6.meta_key = "entity-payment-status-next" OR mt7.post_id IS NULL )
) AND posts.post_type IN ("clinic", "doctor") GROUP BY posts.ID
    ';

//*/

$args = [
    'post_type' => ['clinic', 'doctor'],
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'entity-tariff-till',
            'value' => time(),
            'compare' => '<'
        ],
        [
            'relation' => 'OR',
            [
                'key' => 'entity-timezone',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'entity-timezone',
                'compare' => 'NOT EXISTS'
            ],
        ],
        [
            'relation' => 'OR',
            [
                'key' => 'entity-timezone-bias',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'entity-timezone-bias',
                'compare' => 'NOT EXISTS'
            ],
        ],
        [
            'relation' => 'OR',
            [
                'key' => 'entity-tariff-next',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'entity-tariff-next',
                'compare' => 'NOT EXISTS'
            ],
        ],
        [
            'relation' => 'OR',
            [
                'key' => 'entity-payment-status-next',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'entity-payment-status-next',
                'compare' => 'NOT EXISTS'
            ],
        ],
    ],
];
$outdated = new WP_Query( $args );
    
FCP_Forms::json_field_by_sibling( $this->s->fields, 'entity-tariff', [
    'type' => 'notice',
    'meta_box' => true,
    'before' => '<pre>',
    'after' => '</pre>',
    'text' => "\n".
        print_r( $outdated->request, true )//fcp_flush_tariff_by_id( $_GET['post'] )
    ."\n",
], 'before' );
//*/


include 'variables.php';

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
if ( $values['entity-tariff-billed'] && $values['entity-payment-status'] === 'billed' ) {
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
            'text' => '<strong>The next tariff option is available to users '.( $prolong_gap / $day ).' days before the current <em>paid</em> tariff ends.</strong><span>If current tariff is free, you can schedule the paid one.</span>',
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
            $status_message = '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The initial free tariff will be restored automatically in '.floor( $prolong_gap / $day / 7 ).' weeks, if not payed.';

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
            $status_message = '<em><font color="#35b32d">Payment status - Billed</font>: </em>Please check your billing email ' . $billing_email . ' and pay the bill to activate the tariff. For any questions please contact our accountant by <a href="mailto:buchhaltung@firmcatalyst.com">buchhaltung@firmcatalyst.com</a><br>The initial free tariff will be restored automatically in '.floor( $prolong_gap / $day / 7 ).' weeks, if not payed.';

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
    array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>The next tariff will be activated on <font color="#35b32d" style="white-space:nowrap">'.$tariff_next_start_label.'</font></p>',
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