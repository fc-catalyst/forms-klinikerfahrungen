<?php
/*
Process the form data
*/

/*
if ( is_user_logged_in() ) {
    return;
}
/*
if ( wp_signon() instanceof WP_Error ) { // ++use is_wp_error( wp_signon() ) here
    $warning = 'User-Name or Password is not correct. Try again.';
    return;
}
//*/
return;
// upload media files to media library
if( !$_FILES ) {
    return;
}

/*
echo '<pre>';
print_r( $_FILES );
echo '</pre>';
//*/

// ++move it to validate.class.php?
$upload = [];
foreach ( $json->fields as $v ) {
    if ( $v->type != 'file' ) {
        continue;
    }

    if ( $warns->result[ $v->name ] ) {
        // continue;

        // multiple files exception
        if ( !$v->multiple ) {
            continue;
        }

        $mflip = FCP_Forms__Validate::flipFiles( $_FILES[$v->name] );
        foreach ( $mflip as $w ) {
            if ( in_array( $w['name'], $warns->mFilesFailed[ $v->name ] ) ) {
                continue;
            }
            $w['field'] = $v->name;
            $upload[] = $w;
        }
        
        continue;
    }
    
    $upload[] = $_FILES[ $v->name ] + [ 'field' => $v->name ];
}

if ( empty( $upload ) ) {
    return;
}

// upload to wordpress media library

$tmp_FILES = $_FILES;
$_FILES = $upload;
/*
echo '<pre>';
print_r( $_FILES );
echo '</pre>';
return;
//*/
if ( !function_exists( 'wp_generate_attachment_metadata' ) ) {
    require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
    require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
    require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
}

foreach ( $_FILES as $k => $v ) {
    if ( $v['error'] !== UPLOAD_ERR_OK ){
        continue;
    }

    $aid = media_handle_upload( $k, 0 );
    echo $aid . '  ' . wp_get_attachment_image( $aid, 'thumbnail' );
    if ( is_wp_error( $aid ) ) {
        $warns->result[ $v['field'] ][] = 'WordPress upload error for <em>' . $v['name'] . '</em>';
        continue;
    }
    // ++replace with goodies?
    $warns->result[ $v['field'] ][] =
        wp_get_attachment_image( $aid, 'thumbnail', false, ['class'=>'fcp-upload-preview'] ).
        '<em>' . $v['name'] . '</em> is uploaded';

}

$_FILES = $tmp_FILES;
