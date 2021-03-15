<?php
/*
Process the form data
*/

if ( is_user_logged_in() ) {
    return;
}

if ( wp_signon() instanceof WP_Error ) {
    $warning = 'User-Name or Password is not correct. Try again.';
}

