<?php
/*
Process the form data
*/

if ( $warning || !empty( $warns->result ) ) {
    return;
}

$email = wp_slash( $_POST['user-email'] );
$login = sanitize_title( $email );

$register = wp_insert_user( [
    'user_login' => $login,
	'user_email' => $email,
	'user_pass' => $_POST['user-password'],
	'role' => 'clinic_representative'
]);

if ( is_wp_error( $register ) ) {
    foreach ( $register->errors as $v ) {
        $warns->add_result( 'user-email', implode( '<br />', $v ) );
    }
    return;
}

// log in
$creds['user_login'] = $login;
$creds['user_password'] = $_POST['user-password'];
$creds['remember'] = false;

$user = wp_signon( $creds, false );

if ( is_wp_error( $user ) ) {
   $warning = $user->get_error_message();
}
