<?php

// post type for billing

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType( [
    'name' => 'Rechnungsdaten',
    'type' => 'billing',
    'slug' => 'rechnung',
    'plural' => 'Rechnungsdaten',
    'description' => 'The list of payment options',
    'fields' => ['title', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => false,
    'gutenberg' => false,
    'menu_position' => 23,
    'menu_icon' => 'dashicons-money-alt',
    'has_archive' => false,
    'capability_type' => ['entity', 'entities']
] );


// meta fields for new post types on basis of the form structure

if ( !class_exists( 'FCP_Add_Meta_Boxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$json = FCP_Forms::structure( $dir );
if ( $json === false ) { return; }

new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'Rechnungsdaten',
    'post_types' => ['billing'],
    'context' => 'normal',
    'priority' => 'high'
] );
