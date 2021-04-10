<?php
/*

create post type Clinics (kliniken)
create meta fields for both, according to structure
process.php to fill in the fields

*/

// post type for clinics

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType( [
    'name' => 'Clinic',
    'slug' => 'kliniken',
    'plural' => 'Clinics',
    'description' => 'The list of registered clinics',
    'fields' => [ 'title', 'editor', 'custom-fields' ],
    'hierarchical' => false,
    'public' => true,
    'gutenberg' => true,
    'menu_position' => 21,
    'menu_icon' => 'dashicons-plus-alt',
    'has_archive' => true
] );


// meta fields for new post types on basis of the form structure

if ( !class_exists( 'FCPAddMetaBoxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$cont = file_get_contents( $this->forms_path . $file . '/structure.json' );
$json = json_decode( $cont, false );

new FCPAddMetaBoxes( $json, (object) [
    'name' => $file,
    'title' => 'Clinic Information',
    'post_types' => ['kliniken'],
    'context' => 'normal',
    'priority' => 'high'
] );


// meta boxes front-end ++can probably make it universal

add_action( 'admin_enqueue_scripts', function($hook) {

    if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( !isset( $screen ) || !is_object( $screen ) || $screen->post_type != 'kliniken' ) {
        return;
    }

    wp_enqueue_style( 'fcp-forms-adm', $this->self_url . 'style.css', [], $this->css_ver );
    wp_enqueue_script( 'fcp-forms-adm', $this->self_url . 'scripts.js', ['jquery'], $this->js_ver );

});
