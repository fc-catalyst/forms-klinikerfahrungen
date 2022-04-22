<?php
/*
Process the form data
*/

if ( $warning || !empty( $warns->result ) ) { return; }

$message = array_merge( $_POST, [
    'subject' => sprintf( __( 'Message from %s', 'fcpfo' ),  get_bloginfo( 'name' ) )
]);

require_once __DIR__ . '/../../mail/mail.php';

if ( FCP_FormsTariffMail::to_moderator_custom( $message ) ) {
    $redirect = add_query_arg( 'success', '', get_permalink() );
    return;
}
$redirect = add_query_arg( 'fail', '', get_permalink() );