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
    wp_clear_scheduled_hook( 'fcp_forms_entity_tariff_scheduled' );
    $day_start = mktime( 0, 0, 0 );
    // hourly because of timezones, not counting not standard 45 and 30 min gaps, though, for later, maybe
    wp_schedule_event( $day_start, 'hourly', 'fcp_forms_entity_tariff_scheduled' );
});

register_deactivation_hook( $this->self_path_file, function() {
    wp_clear_scheduled_hook( 'fcp_forms_entity_tariff_scheduled' );
});

function fcp_forms_entity_tariff_scheduled() {
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

/*
    foreach ( $outdated as $v ) { //JUST RENAME THE FIELDS NAMES - META_KEY!!! and check the replace syntax

        // ++ can replace with 1 delete and 1 insert queries!!!

        // replace the tariff with next tariff values
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

        // ++if no prolonging - return to the free account
        // ++flush the cache for the ID
        
    }
    
    // select not payed in a period entities to flush
    $notpayed = $wpdb->get_results( '
        SELECT * FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "entity-tariff-requested" AND CAST( `meta_value` ) AS SIGNED ) < '.time().'
    ');
    // ++select the status? go through the logic first again
    
    foreach ( $notpayed as $v ) {
        
    }
//*/
    FCP_Forms::tz_reset();
}

FCP_Forms::tz_reset();