<?php
/*
Process the form data
*/

$email = wp_slash( $_POST['user-email'] );
$login = sanitize_title( $email );

$register = wp_insert_user( [
    'user_login' => $login,
	'user_email' => $email,
	'user_pass' => $_POST['user-password'],
	'role' => 'fcp_cl_repr'
]);

if ( is_wp_error( $register ) ) {
    foreach ( $register->errors as $v ) {
        $warns->add_result( 'user-email', implode( '<br />', $v ) );
    }
    return;
}

// successful register - advice to login
