<?php

class FCPAddPostType {

    private $p;

    public static function version() {
        return '2.0.1';
    }

    public function __construct($p) {

        $this->p = $p; // post type prefs

        add_action( 'init', [ $this, 'addPostType' ] );

        if ( $p['gutenberg'] && $p['gutenberg_allow'] ) {
            add_filter( 'allowed_block_types', [ $this, 'limitGutenberg' ], 10, 2 );
        }

    }

    public function addPostType() {

        $td = FCP_Forms::$text_domain;
        $p = $this->p;
    
        $labels = [
            'name'                => __( $p['plural'], $td ),
            'singular_name'       => __( $p['name'], $td ),
            'menu_name'           => __( $p['plural'], $td ),
            'all_items'           => __( 'All ' . $p['plural'], $td ),
            'view_item'           => __( 'View ' . $p['name'], $td ),
            'add_new_item'        => __( 'Add New ' . $p['name'], $td ),
            'add_new'             => __( 'Add New', $td ),
            'edit_item'           => __( 'Edit ' . $p['name'], $td ),
            'update_item'         => __( 'Update ' . $p['name'], $td ),
            'search_items'        => __( 'Search ' . $p['name'], $td ),
            'not_found'           => __( $p['name'] . ' Not Found', $td ),
            'not_found_in_trash'  => __( $p['name'] . ' Not found in Trash', $td ),
        ];
            
        $args = [
            'label'               => __( $p['slug'], $td ),
            'description'         => __( $p['description'], $td ),
            'labels'              => $labels,
            'supports'            => $p['fields'],
            'hierarchical'        => $p['hierarchical'],
            'public'              => $p['public'],
            'show_in_rest'        => $p['gutenberg'],
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'menu_position'       => $p['menu_position'],
            'menu_icon'           => $p['menu_icon'],
            'can_export'          => true,
            'has_archive'         => $p['has_archive'],
            'exclude_from_search' => $p['public'] ? false : true,
            'publicly_queryable'  => $p['public'],
            'capability_type'     => 'page',
        ];
            
        register_post_type( $p['slug'], $args );
        
        // can add slug override and other here

    }
    
    public function limitGutenberg( $allowed_blocks ) {
    
        global $post;
        $p = $this->p;

        if ( $post->post_type !== $p['slug'] || !$p['gutenberg_allow'] ) {
            return $allowed_blocks;
        }

        return $p['gutenberg_allow'];
    }

}
