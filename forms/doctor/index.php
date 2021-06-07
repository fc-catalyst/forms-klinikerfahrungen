<?php

// post type for clinics

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType( [
    'name' => 'Doctor',
    'type' => 'doctor',
    'slug' => 'doctor',
    'plural' => 'My Doctors',
    'description' => 'The list of registered doctors',
    'fields' => ['title', 'comments', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => false,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-businessperson',
    'has_archive' => true,
    'capability_type' => ['entity', 'entities']
] );


// pages templates ++move the templates to the FCPADDPostType class

add_filter( 'template_include', function( $template ) {

    $new_template = $template; // default theme template
    $path = $this->forms_path . 'clinic/templates/';

    if ( is_singular( 'doctor' ) ) {
        $new_template = $path . 'clinic-template.php';
    }

    if ( is_post_type_archive( 'doctor' ) ) {
        $new_template = $path . 'clinic-archive.php';
    }

    if ( file_exists( $new_template ) ) {
        return $new_template;
    }

    return $template;

}, 99 );

/*
add_action( 'pre_get_posts', function( $query ) {

    $url = explode( "/", $_SERVER['REQUEST_URI'] ); // do in a different way!!

    if ( $url[1] == 'clinic' ) {
        $query->is_main_query();
        $query->set( 'posts_per_page', 10 );
    }

} );
*/

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
    'title' => 'Clinic Information',
    'post_types' => ['doctor'],
    'context' => 'normal',
    'priority' => 'high'
] );
