<?php
/*
    Overall settings for the form
*/

// add new user type
register_activation_hook( $this->self_path_file, function() {

    add_role( 'entity_delegate', 'Clinic / Doctor', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => false
    ]);

});

register_deactivation_hook( $this->self_path_file, function() {
    remove_role( 'entity_delegate' );
});

add_action( 'init', function () { // can be admin_init

    $role = get_role( 'entity_delegate' );

    $role->add_cap( 'read' );
    $role->add_cap( 'read_entity' );
    $role->add_cap( 'read_private_entities' );
    $role->add_cap( 'edit_entity' );
    $role->add_cap( 'edit_entities' );
    $role->add_cap( 'edit_published_entities', false );
    //$role->add_cap( 'edit_others_entities', false );
    $role->add_cap( 'publish_entities', false );
    $role->add_cap( 'delete_private_entities' );
    $role->add_cap( 'delete_published_entities' );


    $role = get_role( 'administrator' );

    $role->add_cap( 'read' );
    $role->add_cap( 'read_entity' );
    $role->add_cap( 'read_private_entities' );
    $role->add_cap( 'edit_entity' );
    $role->add_cap( 'edit_entities' );
    $role->add_cap( 'edit_others_entities' );
    $role->add_cap( 'edit_published_entities' );
    $role->add_cap( 'publish_entities' );
    $role->add_cap( 'delete_others_entities' );
    $role->add_cap( 'delete_private_entities' );
    $role->add_cap( 'delete_published_entities' );

}, 20 );

// modify the admin for the role

// disable front-end admin bar
add_action( 'plugins_loaded', function() {
    if ( !self::check_role( 'entity_delegate' ) ) { return; }
    show_admin_bar( false );
});

// style the wp-admin
add_action( 'admin_enqueue_scripts', function() use ($dir) {
    wp_enqueue_style( 'fcp-forms-'.$dir.'-admin', $this->forms_url . $dir . '/style-admin.css', [], $this->css_ver );
});

// remove the wp logo
add_action( 'wp_before_admin_bar_render', function() {
    if ( !self::check_role( 'entity_delegate' ) ) { return; }

    global $wp_admin_bar;
    $wp_admin_bar->remove_node( 'wp-logo' );
}, 0 );

// disable dashboard
add_action( 'admin_menu', function(){  
    if ( !self::check_role( 'entity_delegate' ) ) { return; }

    remove_menu_page( 'index.php' );
});

// redirect from dashboard to the list of clinics
add_action( 'admin_enqueue_scripts', function() {
    if ( !self::check_role( 'entity_delegate' ) ) { return; }
    if ( get_current_screen()->id != 'dashboard' ) { return; }

    wp_redirect( get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic' );
});

// remove the list of post-groups
add_action( 'admin_init', function() {
    if ( !self::check_role( 'entity_delegate' ) ) { return; }
    add_filter( 'views_edit-clinic', '__return_null' );
});

// login redirect to referrer ( works only from wp-login.php )
add_filter( 'login_redirect', function( $redirect_to, $requested_redirect_to, $user ) {
    if ( !self::check_role( 'entity_delegate', $user ) ) { return $redirect_to; }
    if ( explode( '?', basename( $_SERVER['HTTP_REFERER'] ) )[0] == 'wp-login.php' ) {
        return get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic';
    }
    return $_SERVER['HTTP_REFERER'];
}, 10, 3 );

// logout redirect home
add_action( 'wp_logout', function() {
    // if ( !self::check_role( 'entity_delegate' ) ) { return; } // it doesn't work here :( BUT make the log out message on home page very obvious!!
    wp_safe_redirect( home_url() );
    exit;
});

// hide some elements from the entity_delegate to not distrub
add_action( 'admin_footer', function() {
    if ( !self::check_role( 'entity_delegate' ) ) { return; }
    ?>
<style>
    .search-box,
    .tablenav.top,
    .tablenav.bottom,
    .table-view-list.posts tr.author-other,
    .table-view-list.posts tr > td:first-child,
    .table-view-list.posts tr > th:first-child,
    .show-admin-bar.user-admin-bar-front-wrap,
    #collapse-menu,
    #wpfooter {
        display:none;
    }
</style>
    <?php
});
