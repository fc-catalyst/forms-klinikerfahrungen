<?php

// post types for clinics & doctors

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

register_activation_hook( $this->self_path_file, function() {
    add_action( 'wp_loaded', function() {
        flush_rewrite_rules();
    });
});
register_deactivation_hook( $this->self_path_file, function() {
    flush_rewrite_rules();
});

new FCPAddPostType( [
    'name' => 'Clinic', // the translation goes inside the class
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
    'capability_type' => ['entity', 'entities'],
    'text_domain' => 'fcpfo-ea',
] );

new FCPAddPostType( [ // basically the clone of clinics for now
    'name' => 'Doctor',
    'type' => 'doctor',
    'slug' => 'doctor',
    'plural' => 'Doctors',
    'description' => 'The list of doctors, registered by you',
    'fields' => ['title', 'comments', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => false,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-insert',
    'has_archive' => true,
    'capability_type' => ['entity', 'entities'],
    'text_domain' => 'fcpfo-ea',
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

// style the wp-admin // it is not in fcp-forms.php as it might have more conditions to appear
add_action( 'admin_enqueue_scripts', function($hook) use ($dir) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) { return; }

    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) ) { return; }
    
    if ( !in_array( $screen->post_type, ['clinic', 'doctor'] ) ) { return; }

    wp_enqueue_script(
        'fcp-forms-entity-admin',
        $this->forms_url . $dir . '/scripts-admin.js',
        ['jquery'],
        $this->js_ver 
    );
});


// add translation languages
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'fcpfo-ea', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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

/* moved to override.php && override-admin.php
global $wpdb;
$options = $wpdb->get_col( '
    SELECT `meta_value`
    FROM `'.$wpdb->postmeta.'`
    WHERE `meta_key` = "entity-specialty" AND `meta_value` <> ""
    GROUP BY `meta_value` ASC
');
FCP_Forms::save_options( 'entity-specialty', $options );
//*/

new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'Unternehmensinformationen',
    'post_types' => ['clinic', 'doctor'],
    'context' => 'normal',
    'priority' => 'high'
] );

// disable yoast schema, as the types have their own markup
add_filter( 'wpseo_json_ld_output', function() {
    if ( is_singular( 'clinic' ) || is_singular( 'doctor' ) ) {
        return false;
    }
});

// ++it is here, because I haven't found a better place for it yet
add_shortcode( 'fcp-get-to-print', function($atts = []) {

    $allowed = [
        '_get' => '',
        '_post' => '',
        'html' => '',
    ];

    $atts = shortcode_atts( $allowed, $atts ); // ++ add that modifying function of mine to change a="" to just a
    
    if ( $atts['_get'] && isset( $_GET[ $atts['_get'] ] ) && $atts['html'] ) {
        return $atts['html'];
    }
    
    if ( $atts['_post'] && isset( $_GET[ $atts['_post'] ] ) && $atts['html'] ) {
        return $atts['html'];
    }
});