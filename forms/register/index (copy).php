<?php

// ++export to a class
// ++add post_type_exists()

// add new user type
register_activation_hook( $this->self_path, function() {

    add_role( 'fcp_repr', 'Clinic Representative', [
        'read' => true
    ]);

});

register_deactivation_hook( $this->self_path, function() {
    remove_role( 'fcp_repr' );
});

add_action( 'admin_init',function() {

    $ctp = 'kliniken';

    $role = get_role( 'fcp_repr' );

    $role->add_cap( 'read_' . $ctp);
    $role->add_cap( 'edit_' . $ctp );
    $role->add_cap( 'edit_' . $ctp );
    $role->add_cap( 'edit_other_' . $ctp );
    //$role->add_cap( 'edit_published_' . $ctp );
    //$role->add_cap( 'publish_' . $ctp );
    $role->add_cap( 'read_private_' . $ctp );
    $role->add_cap( 'delete_' . $ctp );


}, 999 );

function change_capabilities_of_CPT( $args, $post_type ){

 // Do not filter any other post type
 if ( 'my_CPT' !== $post_type ) { // my_CPT == Custom Post Type == 'job' or other

     // if other post_types return original arguments
     return $args;

 }


// This is the important part of the capabilities 
/// which you can also do on creation ( and not by filtering like in this example )


 // Change the capabilities of my_CPT post_type
 $args['capabilities'] = array(
            'edit_post' => 'edit_my_CPT',
            'edit_posts' => 'edit_my_CPT',
            'edit_others_posts' => 'edit_other_my_CPT',
            'publish_posts' => 'publish_my_CPT',
            'read_post' => 'read_my_CPT ',
            'read_private_posts' => 'read_private_my_CPT',
            'delete_post' => 'delete_my_CPT'
        );

  // Return the new arguments
  return $args;

}
