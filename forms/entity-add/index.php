<?php

// post types for clinics & doctors

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType( [
    'name' => 'Clinic',
    'type' => 'clinic',
    'slug' => 'kliniken',
    'plural' => 'Clinics',
    'description' => 'The list of clinics, registered by you',
    'fields' => ['title', 'comments', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => false,
    'menu_position' => 21,
    'menu_icon' => 'dashicons-plus-alt',
    'has_archive' => true,
    'capability_type' => ['entity', 'entities']
] );

new FCPAddPostType( [ // basically the clone of clinics for now
    'name' => 'Doctor',
    'type' => 'doctor',
    'slug' => 'doctor',
    'plural' => 'Doctors',
    'description' => 'The list of registered doctors, registered by you',
    'fields' => ['title', 'comments', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => false,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-insert',
    'has_archive' => true,
    'capability_type' => ['entity', 'entities']
] );


// pages templates ++move the templates to the FCPADDPostType class

add_filter( 'template_include', function( $template ) {

    $new_template = $template; // default theme template
    $path = $this->forms_path . 'entity-add/templates/'; // ++get the dir name automatically for all

    if ( is_singular( 'clinic' ) || is_singular( 'doctor' ) ) {
        $new_template = $path . 'entity-template.php'; // ++rename these with not prefix
    }

    if ( is_post_type_archive( 'clinic' ) || is_post_type_archive( 'doctor' ) ) {
        $new_template = $path . 'entities-archive.php';
    }

    if ( file_exists( $new_template ) ) {
        return $new_template;
    }

    return $template;

}, 99 );

add_filter( 'comments_template', function( $template ) {

    $new_template = $template; // default theme template
    $path = $this->forms_path . 'entity-add/templates/';

    if ( is_singular( 'clinic' ) || is_singular( 'doctor' ) ) {
		$new_template = $path . 'entity-comments.php';
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

// style the wp-admin // ++move to main maybe?
add_action( 'admin_enqueue_scripts', function() use ($dir) {
    wp_enqueue_script(
        'fcp-forms-entitiy-admin',
        $this->forms_url . $dir . '/scripts-admin.js',
        ['jquery'],
        $this->js_ver 
    );
});

// meta fields for new post types on basis of the form structure

if ( !class_exists( 'FCP_Add_Meta_Boxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$json = FCP_Forms::structure( $dir );
if ( $json === false ) { return; }

global $wpdb;
$options = $wpdb->get_col( '
    SELECT `meta_value`
    FROM `'.$wpdb->postmeta.'`
    WHERE `meta_key` = "entity-specialty" AND `meta_value` <> ""
    GROUP BY `meta_value` ASC
');
FCP_Forms::save_options( 'entity-specialty', $options );


new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'The Information',
    'post_types' => ['clinic', 'doctor'],
    'context' => 'normal',
    'priority' => 'high'
] );
