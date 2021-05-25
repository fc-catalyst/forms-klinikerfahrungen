<?php
/*
Print something else instead of the form
*/

if ( is_user_logged_in() ) {
    // unset( $json->fields );
    $override = '<h2>Hello, ' . wp_get_current_user()->display_name . '</h2>';
    return;
}
