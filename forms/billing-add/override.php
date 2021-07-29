<?php
/*
Print something else instead of the form
*/
return;
if ( !is_user_logged_in() ) {
    unset( $json->fields );
    $override = '';
    return;
}
