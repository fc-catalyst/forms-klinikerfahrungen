<?php

// add new user type
register_activation_hook( $this->self_path_file, function() {

    add_role( 'clinic_representative', 'Clinic Representative', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => false
    ]);

});

register_deactivation_hook( $this->self_path_file, function() {
    remove_role( 'clinic_representative' );
});

add_action( 'init', function () { // can be admin_init

    $role = get_role( 'clinic_representative' );

    $role->add_cap( 'read' );
    $role->add_cap( 'read_clinic' );
    $role->add_cap( 'read_private_clinics' );
    $role->add_cap( 'edit_clinic' );
    $role->add_cap( 'edit_clinics' );
    $role->add_cap( 'edit_published_clinics', false );
    //$role->add_cap( 'edit_others_clinics', false );
    $role->add_cap( 'publish_clinics', false );
    $role->add_cap( 'delete_private_clinics' );
    $role->add_cap( 'delete_published_clinics' );


    $role = get_role( 'administrator' );

    $role->add_cap( 'read' );
    $role->add_cap( 'read_clinic' );
    $role->add_cap( 'read_private_clinics' );
    $role->add_cap( 'edit_clinic' );
    $role->add_cap( 'edit_clinics' );
    $role->add_cap( 'edit_others_clinics' );
    $role->add_cap( 'edit_published_clinics' );
    $role->add_cap( 'publish_clinics' );
    $role->add_cap( 'delete_others_clinics' );
    $role->add_cap( 'delete_private_clinics' );
    $role->add_cap( 'delete_published_clinics' );

}, 20 );

// disable front-end admin bar for the representative
//*
add_action( 'plugins_loaded', function() {
    if ( !fcp_forms_register_cr() ) { return; }
    show_admin_bar( false );
});
// ++ ++ add hidding the checkbox from the admin
//*/

// style the wp-admin for the role
add_action( 'admin_enqueue_scripts', function() use ($dir) {
    //if ( !fcp_forms_register_cr() ) { return; }
    wp_enqueue_style( 'fcp-forms-'.$dir.'-admin', $this->forms_url . $dir . '/style-admin.css', [], $this->css_ver );
});

// remove the wp logo
add_action( 'wp_before_admin_bar_render', function() {
    if ( !fcp_forms_register_cr() ) { return; }
    //if ( !is_admin() ) { return; }

    global $wp_admin_bar;
    $wp_admin_bar->remove_node( 'wp-logo' );
    //$wp_admin_bar->remove_node( 'new-content' ); // add new post
    //$wp_admin_bar->remove_node( 'view' ); // view single post
    //$wp_admin_bar->remove_node( 'archive' ); // view posts archive
    //$wp_admin_bar->remove_node( 'logout' );    // logout under Howdy
    //$wp_admin_bar->remove_node( 'user-info' );    // under Howdy
    //$wp_admin_bar->remove_node( 'edit-profile' ); // under Howdy
    //$wp_admin_bar->remove_node( 'search' );
    //$wp_admin_bar->remove_node( 'my-account' );
}, 0 );

// disable dashboard
add_action( 'admin_menu', function(){  
    if ( !fcp_forms_register_cr() ) { return; }

    remove_menu_page( 'index.php' );                  //Dashboard
    //remove_menu_page( 'edit.php' );                   //Posts  
    //remove_menu_page( 'upload.php' );                 //Media  
    //remove_menu_page( 'edit.php?post_type=page' );    //Pages  
    //remove_menu_page( 'edit-comments.php' );          //Comments  
    //remove_menu_page( 'themes.php' );                 //Appearance  
    //remove_menu_page( 'plugins.php' );                //Plugins  
    //remove_menu_page( 'users.php' );                  //Users  
    //remove_menu_page( 'tools.php' );                  //Tools  
    //remove_menu_page( 'options-general.php' );        //Settings
}); 
// redirect from dashboard to the list of clinics
add_action( 'admin_enqueue_scripts', function() {
    if ( !fcp_forms_register_cr() ) { return; }
    if ( get_current_screen()->id != 'dashboard' ) { return; }
    wp_redirect( get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic' );
});

// remove the list of post-groups
add_action( 'admin_init', function() {
    if ( !fcp_forms_register_cr() ) { return; }
    add_filter( 'views_edit-clinic', '__return_null' );
});

function fcp_forms_register_cr() { // check if role == representative
    $user = wp_get_current_user();
    if( empty( $user ) || !count( array_intersect( [ 'clinic_representative' ], (array) $user->roles ) ) ) {
        return false;
    }
    return true;
}


// hide some elements from the representative to not distrub
add_action( 'admin_footer', function() {
    if ( !fcp_forms_register_cr() ) { return; }
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
