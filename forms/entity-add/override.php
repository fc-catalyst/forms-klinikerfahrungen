<?php
/*
Print something else instead of the form
*/

if ( !is_user_logged_in() ) {
    unset( $json->fields );
    $override = '';
    return;
}


// autofill some values
$current_user = wp_get_current_user();
FCP_Forms::json_attr_by_name( $json->fields, 'entity-email', 'value', $current_user->user_email );