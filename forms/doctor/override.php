<?php
/*
Print something else instead of the form
*/
return;
if ( !is_user_logged_in() ) {
    unset( $json->fields );
    // ++tmp redirect to home page
    return;
}
// ++compare the permissions

//$user = wp_get_current_user();
