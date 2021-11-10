<?php
/*
Print something else instead of the form
*/

if ( !is_user_logged_in() ) {
    unset( $json->fields );
    $override = '';
    return;
}


// pick all billings of current user
$wp_query = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => 'billing',
    'orderby' => 'post_title',
    'order'   => 'ASC',
    'post_status' => 'any',
    'posts_per_page' => -1,
]);

if ( !$wp_query->have_posts() ) {
    unset( $json->fields );
    $override = '';
    return;
}


// add billing the options
$billings = [];
while ( $wp_query->have_posts() ) {
    $wp_query->the_post();
    $billings[ get_the_ID() ] = get_the_title();
}
wp_reset_query();

$json->fields = FCP_Forms::json_change_field( $json->fields,
    'billing-id',
    'options',
    (object) $billings
);


// advice the entity option
if ( isset( $_GET['step3'] ) ) {

    // pick the newes entity meta
    $wp_query = new WP_Query([
        'author' => wp_get_current_user()->ID,
        'post_type' => ['clinic', 'doctor'],
        'orderby' => 'ID',
        'order'   => 'DESC',
        'post_status' => 'any',
        'posts_per_page' => 1,
    ]);

    if ( $wp_query->have_posts() ) {
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();

            // print the notice
            $json->fields = FCP_Forms::json_change_field( $json->fields,
                'entity-id',
                'type',
                'notice'
            );
            $json->fields = FCP_Forms::json_change_field( $json->fields,
                'entity-id',
                'text',
                get_the_title()
            );
/*
            $json->fields = FCP_Forms::json_change_field( $json->fields,
                'entity-id',
                'name',
                'entity-notice'
            );
//*/
            // add the hidden field value
            array_push( $json->fields, (object) [ // ++unify $json->fields methods in main class
                'type' => 'hidden',
                'name' => 'entity-id',
                'value' => get_the_ID(),
            ]);
            
            // just a notice
            array_push( $json->fields, (object) [ // ++can go higher - before submit button
                'type' => 'notice',
                'text' => '<p>Nach Bestätigung der Registrierung, erhalten Sie in Kürze eine Rechnung.</p>',
            ]);

            break;
        }
        wp_reset_query();
    }
    
    return;
}

// pick all user's entities
$wp_query = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'post_title',
    'order'   => 'ASC',
    'post_status' => 'any',
    'posts_per_page' => -1,
]);

if ( $wp_query->have_posts() ) {

    $entities = [];
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        $entities[ get_the_ID() ] = get_the_title();
    }
    wp_reset_query();
    
    $json->fields = FCP_Forms::json_change_field( $json->fields,
        'entity-id',
        'options',
        (object) $entities
    );
    
}