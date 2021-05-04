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

// disable admin bar for the user
/*
add_action( 'plugins_loaded', function() {
    if ( current_user_can( 'clinic_representative' ) ) {
        show_admin_bar( false );
    }
});
// ++ ++ add hidding the checkbox from the admin
//*/
