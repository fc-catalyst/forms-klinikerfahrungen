<?php

// add new user type
register_activation_hook( $this->self_path_file, function() {

    add_role( 'fcp_cl_repr', 'Clinic Representative', [
        'read' => true
    ]);

});

register_deactivation_hook( $this->self_path_file, function() {
    remove_role( 'fcp_cl_repr' );
});

// disable admin bar for the user
add_action( 'plugins_loaded', function() {
    if ( current_user_can( 'fcp_cl_repr' ) ) {
        show_admin_bar( false );
    }
});
