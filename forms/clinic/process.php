<?php
/*
Process the form data
*/

/*
    upload to tmp
    upload to normal if user is logged in and id is provided
    fill in the hidden field
    save data to the engine
    make the template
    make the front-end for maps
//*/


if ( !is_user_logged_in() ) {
    $warning = 'Please log in to use the form';
    return;
}

// upload the files to tmp directory
if ( $_FILES ) {

    echo '**0<pre>';
    print_r( $uploads->files );
    print_r( $uploads->tmps );
    echo '</pre>';

    $uploads->tmp_upload(); // if is true?
    
    echo '**1<pre>';
    print_r( $uploads->files );
    print_r( $uploads->tmps );
    echo '</pre>';

}


if ( !$warning && empty( $warns->result ) ) {
    
    // create new post
    $id = wp_insert_post( [
        'post_title' => sanitize_text_field( $_POST['company-name'] ),
        'post_content' => '',
        'post_status' => 'private', // ++ pending
        'post_author' => get_the_author_meta()['ID'],
        'post_type' => 'kliniken'
    ]);
    if ( $id === 0 ) {
        $warning = 'Unexpected WordPress error';
        return;
    }
    
    // meta data is added automatically with save_post hook
    
    // upload the files & add them to meta
    if ( !empty( $uploads->tmps ) ) {

        $dir = wp_get_upload_dir()['basedir'] . '/' . 'kliniken' . '/' . $id;
        if ( !mkdir( $dir, 0777, true ) ) {
            $warning = "Can't create the folder for the files";
            return;
        }
        if ( $uploads->tmp_move( $dir ) !== true ) {
            $warning = "Files are not uploaded";
            return;
        }
        foreach ( $uploads->tmps_to_meta() as $k => $v ) {
            update_post_meta( $id, FCP_Forms::$prefix . $_POST['fcp-form-name'] . '_' . $k, $v );
        }
        
    }
    
    // $redirect to newly created clinic with the awesomely designed template and personal button to modify the data

}
