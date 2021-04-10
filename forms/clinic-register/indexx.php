<?php
/*

create post type Clinics (kliniken)
create post type Billing Details
create meta fields for both, according to structure
create new user type to operate only own records
process.php to fill in the fields


*/

// post types for clinics and billing details

if ( !class_exists( 'FCPAddPostType' ) ) {
    include_once $this->self_path . 'classes/add-post-type.class.php';
}

new FCPAddPostType (
    (object) [
        'text_domain' => FCP_Forms::$text_domain
    ],
    [
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
        'has_archive' => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true
    ]
);

new FCPAddPostType (
    (object) [
        'text_domain' => FCP_Forms::$text_domain
    ],
    [
        'name' => 'Billing Details',
        'slug' => 'bill',
        'plural' => 'Billing Details',
        'description' => 'Billing Details of Clinics',
        'fields' => [ 'title', 'custom-fields' ],
        'hierarchical' => false,
        'public' => false,
        'gutenberg' => false,
        'menu_position' => 22,
        'menu_icon' => 'dashicons-media-text',
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false
    ]
);

// meta fields for new post types on basis of the form structure
