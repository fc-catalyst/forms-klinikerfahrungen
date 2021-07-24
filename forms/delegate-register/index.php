<?php
/*
    Overall settings for the form
*/

// add new user type
register_activation_hook( $this->self_path_file, function() {

    add_role( 'entity_delegate', 'Clinic / Doctor', [
        'level_0' => true, // only read
        'edit_entities' => true,
        'edit_published_entities' => true,
        'delete_entities' => true,
/*
        'read_private_entities' => true,
        'edit_others_entities' => false,
        'publish_entities' => false,
        'delete_private_entities' => true,
        'delete_others_entities' => false,
        'delete_published_entities' => true,
//*/
    ]);

});

register_deactivation_hook( $this->self_path_file, function() {
    remove_role( 'entity_delegate' );
});


/* modify the wp-admin for the role */

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

    //wp_redirect( get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic' ); // ++ restore!!
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
    global $wp_roles;
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
