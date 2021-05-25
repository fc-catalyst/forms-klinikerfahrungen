<?php
/*
Process the form data
*/

if ( $warning || !empty( $warns->result ) ) {
    return;
}

$params = FCP_Forms::email_to_user( $_POST['user-email'] );

$register = wp_insert_user( $params + [
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
$creds['user_login'] = $params['user_login'];
$creds['user_password'] = $_POST['user-password'];
$creds['remember'] = false;

$user = wp_signon( $creds, false );

if ( is_wp_error( $user ) ) {
   $warning = $user->get_error_message();
}
