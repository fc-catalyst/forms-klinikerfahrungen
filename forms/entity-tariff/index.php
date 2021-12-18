<?php

FCP_Forms::tz_set();

// meta select for 

if ( !class_exists( 'FCP_Add_Meta_Boxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$json = FCP_Forms::structure( $dir );
if ( $json === false ) { return; }


new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'Tariff',
    'text_domain' => 'fcpfo',
    'post_types' => ['clinic', 'doctor'],
    'context' => 'side',
    'priority' => 'default'
] );


// datepicker
add_action( 'admin_enqueue_scripts', function() {
/*
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui');
//*/
    wp_enqueue_script(
        'jquery-ui-datepicker',
        $this->self_url . 'forms/' . basename( __DIR__ ) . '/assets/jquery-ui.js'
    );
    wp_enqueue_style(
        'jquery-ui-css',
        $this->self_url . 'forms/' . basename( __DIR__ ) . '/assets/jquery-ui.css'
    );
});

add_action( 'admin_footer', function() {
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function($){
            $( '#entity-tariff-till_entity-tariff' ).datepicker( {
                dateFormat : 'dd.mm.yy'
            });
        });
    </script>
    <?php
});


// schedule for clearing and prolonging tariffs
register_activation_hook( $this->self_path_file, function() {
    wp_clear_scheduled_hook( 'fcp_forms_entity_tariff_prolong' );
    $day_start = mktime( 0, 0, 0 );
    // hourly because of timezones; not counting not standard 45 and 30 min gaps, though, for later, maybe
    wp_schedule_event( $day_start, 'hourly', 'fcp_forms_entity_tariff_prolong' );
    wp_schedule_event( $day_start, 'daily', 'fcp_forms_entity_tariff_clean' );
});

register_deactivation_hook( $this->self_path_file, function() {
    wp_clear_scheduled_hook( 'fcp_forms_entity_tariff_prolong' );
    wp_clear_scheduled_hook( 'fcp_forms_entity_tariff_clean' );
});

function fcp_forms_entity_tariff_prolong() {
    FCP_Forms::tz_set();

    $fields = [
        'tariff_next' => 'entity-tariff-next',
        'status_next' => 'entity-payment-status-next',
        'timezone_name' => 'entity-timezone',
    ];
    
    $get_meta = function( $field, $alias ) {
        global $wpdb;
        static $ind = -1;
        $ind++;
        return '
'.( $ind ? 'JOIN (' : 'FROM (' ).'
    SELECT
        posts.ID,
        '.( $ind ? '' : 'mt0.meta_value AS till, #entity-tariff-till' ).'
        IF ( mt4.meta_key = "'.$field.'", mt4.meta_value, NULL ) AS `'.$alias.'`
    FROM `'.$wpdb->posts.'` AS posts
        LEFT JOIN `'.$wpdb->postmeta.'` AS mt0 ON ( posts.ID = mt0.post_id )
        LEFT JOIN `'.$wpdb->postmeta.'` AS mt1 ON ( posts.ID = mt1.post_id )
        LEFT JOIN `'.$wpdb->postmeta.'` AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
        LEFT JOIN `'.$wpdb->postmeta.'` AS mt3 ON ( posts.ID = mt3.post_id )
        LEFT JOIN `'.$wpdb->postmeta.'` AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "'.$field.'" )
    WHERE
        1 = 1
        AND (
            ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < @till_time )
            AND
            ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
            AND
            ( mt3.meta_key = "'.$field.'" OR mt4.post_id IS NULL )
        )
        AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq'.$ind.'
        ';
    };

    $get_metas = function( $fields, $get_meta ) {
        $result = '';
        foreach( $fields as $alias => $field ) {
            $result .= $get_meta( $field, $alias );
        }
        return $result;
    };


    global $wpdb;
    $outdated = $wpdb->get_results( '
SET @till_time = ' . time() . ';
SELECT sq0.ID, till, ' . implode( ', ', array_keys( $fields ) ) . '
' . $get_metas( $fields, $get_meta ) . '
ON sq0.ID = sq' . implode( '.ID AND sq0.ID = sq', array_slice( array_keys( array_values( $fields ) ), 1 ) ) . '.ID
    ');

    foreach( $outdated as $p ) {
        fcp_flush_tariff_by_id( $p );
    }

    FCP_Forms::tz_reset();
}

function fcp_forms_entity_tariff_clean() {

    global $wpdb;
    
    require 'inits.php';
   
    $outdated = $wpdb->get_results( '
SELECT wpfcp_posts.ID FROM wpfcp_posts
INNER JOIN wpfcp_postmeta ON ( wpfcp_posts.ID = wpfcp_postmeta.post_id )
WHERE
    1=1  AND ( 
        ( wpfcp_postmeta.meta_key = "entity-tariff-requested" AND wpfcp_postmeta.meta_value < '.( time() - $requested_flush_gap).' ) 
        OR 
        ( wpfcp_postmeta.meta_key = "entity-tariff-billed" AND wpfcp_postmeta.meta_value < '.( time() - $billed_flush_gap ).' )
    )
    AND
    wpfcp_posts.post_type IN ("clinic", "doctor")
GROUP BY wpfcp_posts.ID
    ');

    foreach( $outdated as $id ) {
        fcp_flush_dates_by_id( $id );
    }
}

function fcp_flush_dates_by_id($id, $check = false, &$values = []) {
    
    if ( !is_numeric( $id ) ) { return; }
    
    // check the values, else - trust and do what has to be done
    global $wpdb;
    
    if ( $check ) {
    
        $query = 'SELECT `meta_key`, `meta_value` FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( `meta_key` = %s OR `meta_key` = %s )';
        $query = $wpdb->prepare( $query, $id, 'entity-tariff-requested', 'entity-tariff-billed' );
        if ( $query === null ) { return; }
        
        $results = $wpdb->get_results( $query );
        // ++ if nothing is outdated - return

    }
    
    $query = 'DELETE FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( `meta_key` = %s OR `meta_key` = %s )';
    if ( $query = $wpdb->prepare( $query, $id, 'entity-tariff-requested', 'entity-tariff-billed' ) {
        $wpdb->query( $query );
        $values['entity-tariff-requested'] = 0;
        $values['entity-tariff-billed'] = 0;
    }
}

function fcp_flush_tariff_by_id($p, &$values = []) {
    if ( !$p ) { return; }
    if ( is_array( $p ) ) { $p = (object) $p; }
    if ( is_object( $p ) && !$p->ID ) { return; }
    if ( is_numeric( $p ) ) {
        $p = (object) [
            'ID' => $p
        ];
    }
    $p->ID = (int) $p->ID; // intval()
    
    $meta_a2q_where = function($arr = null) { // ++send to a separate class for the form?
        static $arr_saved = [];
        if ( !$arr ) { return $arr_saved; }
        $arr_saved = $arr;
        if ( !$arr[0] ) { return '1=1'; } // pick all fields if no elements
        return '`meta_key` = %s' . str_repeat( ' OR `meta_key` = %s', count( $arr ) - 1 );
    };
    $meta_a2q_insert = function($arr = null) use ($p) {
        static $arr_saved = [];
        if ( !$arr ) { return $arr_saved; }
        $arr_saved = [];
        if ( empty( $arr ) ) { return; }
        foreach ( $arr as $k => $v ) { array_push( $arr_saved, $p->ID, $k, $v ); }
        return '( %s, %s, %s )' . str_repeat( ', ( %s, %s, %s )', count( $arr ) - 1 );
    };
    
    // get values if are not provided and check, else - trust and do what has to be done
    if ( $p->ID && count( (array) $p ) === 1 ) {
        global $wpdb;
        
        $q = $meta_a2q_where( ['entity-tariff-till', 'entity-timezone', 'entity-timezone-bias', 'entity-tariff-next', 'entity-payment-status-next'] ); // bias here to compare later*
        
        $query = 'SELECT `meta_key`, `meta_value` FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( '.$q.' )';
        $query = $wpdb->prepare( $query, array_merge( [ $p->ID ], $meta_a2q_where() ) );
        if ( $query === null ) { return; }
        
        $results = $wpdb->get_results( $query );
        foreach ( $results as $v ) { $p->{ $v->meta_key } = $v->meta_value; }
        unset( $results, $q, $query, $v );

        // check if outdated*
        $p->{ 'entity-timezone-bias' } = $p->{ 'entity-timezone-bias' } ? (int) $p->{ 'entity-timezone-bias' } : 0;
        if ( (int) $p->{ 'entity-tariff-till' } - $p->{ 'entity-timezone-bias' } < time() ) { return; }
        
        $p->till = $p->{ 'entity-tariff-till' };
        $p->tariff_next = $p->{ 'entity-tariff-next' };
        $p->status_next = $p->{ 'entity-payment-status-next' };
        $p->timezone_name = $p->{ 'entity-timezone' };
        
    }

    // remove outdated meta
    $q = $meta_a2q_where( ['entity-tariff', 'entity-payment-status', 'entity-tariff-till', 'entity-timezone-bias', 'entity-tariff-next', 'entity-payment-status-next'] );
    $query = 'DELETE FROM `'.$wpdb->postmeta.'` WHERE `post_id` = %d AND ( '.$q.' )';
    if ( $query = $wpdb->prepare( $query, array_merge( [ $p->ID ], $meta_a2q_where() ) ) ) { $wpdb->query( $query ); }

    // prepare the updated data to insert
    $insert = [];
    if ( $p->tariff_next ) {
        $insert['entity-tariff'] = $p->tariff_next;
    }
    if ( $p->status_next ) {
        $insert['entity-payment-status'] = $p->status_next;
    }
    if ( $p->tariff_next ) {
        $insert['entity-tariff-till'] = strtotime( '+1 year', $p->till );

        $zone = new DateTimeZone( $p->timezone_name );
        $insert['entity-timezone-bias'] = $zone->getTransitions( $p->till, $p->till )[0]['offset'];
        unset( $zone );
    }
    
    // insert the updated meta
    if ( !empty( $insert ) ) {
        $query = 'INSERT INTO `'.$wpdb->postmeta.'` ( `post_id`, `meta_key`, `meta_value` ) VALUES '.$meta_a2q_insert( $insert );
        if ( $query = $wpdb->prepare( $query, $meta_a2q_insert() ) ) { $wpdb->query( $query ); }
    }
    
    // modify the $values from the scope
    if ( empty( $values ) ) { return; }
    
    $values['entity-tariff'] = $insert['entity-tariff'] ? $insert['entity-tariff'] : '';
    $values['entity-payment-status'] = $insert['entity-payment-status'] ? $insert['entity-payment-status'] : '';
    $values['entity-tariff-till'] = $insert['entity-tariff-till'] ? $insert['entity-tariff-till'] : 0;
    $values['entity-timezone-bias'] = $insert['entity-timezone-bias'] ? $insert['entity-timezone-bias'] : 0;
    $values['entity-tariff-next'] = '';
    $values['entity-payment-status-next'] = '';

}

FCP_Forms::tz_reset();