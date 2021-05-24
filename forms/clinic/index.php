<?php

// post type for clinics

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType( [
    'name' => 'Clinic',
    'type' => 'clinic',
    'slug' => 'kliniken',
    'plural' => 'Clinics',
    'description' => 'The list of registered clinics',
    'fields' => ['title', 'comments', 'author', 'revisions'],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => false,
    'menu_position' => 21,
    'menu_icon' => 'dashicons-plus-alt',
    'has_archive' => true,
    'capability_type' => ['clinic', 'clinics']
] );


// pages templates ++move the templates to the FCPADDPostType class

add_filter( 'template_include', function( $template ) {

    $new_template = $template; // default theme template
    $path = $this->forms_path . 'clinic/templates/';

    if ( is_singular( 'clinic' ) ) {
        $new_template = $path . 'clinic-template.php';
    }

    if ( is_post_type_archive( 'clinic' ) ) {
        $new_template = $path . 'clinic-archive.php';
    }

    if ( file_exists( $new_template ) ) {
        return $new_template;
    }

    return $template;

}, 99 );

add_action( 'pre_get_posts', function( $query ) {

    $url = explode( "/", $_SERVER['REQUEST_URI'] );

    if ( $url[1] == 'clinic' ) {
        $this->plugin_setup();
        $query->is_main_query();
        $query->set( 'posts_per_page', $this->cases_per_page );
    }

} );


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
    'post_types' => ['clinic'],
    'context' => 'normal',
    'priority' => 'high'
] );


// meta boxes front-end ++can probably make the following universal

add_action( 'admin_enqueue_scripts', function($hook) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type != 'clinic' ) {
        return;
    }

    wp_enqueue_style( 'fcp-forms-layout', $this->self_url . 'layout.css', [], $this->css_ver );
    wp_enqueue_script( 'fcp-forms', $this->self_url . 'scripts.js', ['jquery'], $this->js_ver );

});
/*
add_action( 'admin_footer', function() {
    ?><style>

    .fcp-form-field-w > span,
    .fcp-form-field-w > span + *{
        flex-basis:100%;
    }

</style><?php
});
//*/
