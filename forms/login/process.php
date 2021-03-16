<?php
/*
Process the form data
*/

if ( is_user_logged_in() ) {
    return;
}
/*
if ( wp_signon() instanceof WP_Error ) { // ++use is_wp_error( wp_signon() ) here
    $warning = 'User-Name or Password is not correct. Try again.';
    return;
}
//*/
// upload media files to media library
if( !$_FILES ) {
    return;
}

// ++improve for the multiple files case
$upload = [];
foreach ( $json->fields as $v ) {
    if ( $v->type == 'file' ) {
        if ( $warns->result[ $v->name ] ) {
            continue;
        }
        
        $upload[] = $v->name;
    }
}

if ( empty( $upload ) ) {
    return;
}

if ( !function_exists( 'wp_generate_attachment_metadata' ) ) {
    require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
    require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
    require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
}

foreach ( $upload as $v ) {
    if ( $v['error'] !== UPLOAD_ERR_OK ){
        continue;
    }
    $attach_id = media_handle_upload( $v, 0 );
    if ( is_wp_error( $attach_id ) ) {
        $warns->result[ $v ][] = 'WordPress upload error for ' . $_FILES[$v]['name'];
        continue;
    }
    
    $warns->result[ $v ][] = '<img src="' . wp_get_attachment_url( $attach_id ) . '" />';
}
