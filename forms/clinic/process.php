<?php
/*
Process the form data
*/

$uploads->add_dirs( [
    'company-logo' => FCP_Forms__Files::tmp_dir()[0],
    'company-image' => FCP_Forms__Files::tmp_dir()[0]
]);

$uploads->uploaded_files_get();
$uploads->upload();
$uploads->uploaded_files_set();


/*
if ( !is_user_logged_in() ) {
    $warning = 'Please log in to use the form';
    return;
}
//*/
/*
// if can create this post type
if ( !get_userdata( wp_get_current_user()->ID )->allcaps['edit_clinic'] ) {
    $warning = 'You don\'t have permission to add a clinic';
    return;
}


// upload the files to tmp directory
if ( isset( $_FILES ) ) {
    $uploads->tmp_upload();
}
//*/
/*
if ( !$warning && empty( $warns->result ) ) {
    
    // create new post
    // meta boxes are filled automatically with save_post hook
    $id = wp_insert_post( [
        'post_title' => sanitize_text_field( $_POST['company-name'] ),
        'post_content' => '',
        'post_status' => 'pending',
        'post_author' => wp_get_current_user()->ID,
        'post_type' => 'clinic',
        'comment_status' => 'closed'
    ]);
    if ( $id === 0 ) {
        $warning = 'Unexpected WordPress error';
        return;
    }

    // ++can check if meta boxes are saved here
    
    // upload the files & add them to meta
    if ( !empty( $uploads->tmps ) ) {

        $dir = wp_get_upload_dir()['basedir'] . '/' . 'kliniken' . '/' . $id;
        if ( !mkdir( $dir, 0777, true ) ) {
            $warning = 'Can\'t create the folder for the files';
            return;
        }

        if ( $uploads->tmp_move( $dir ) !== true ) {
            $warning = 'Files are not uploaded: ' . print_r( $uploads->tmp_move( $dir ), true );
            return;
        }
        
        // remove the tmp dir
        FCP_Forms__Files::rm_dir( FCP_Forms__Files::tmp_dir()[1] );

        foreach ( $uploads->tmps_to_meta() as $k => $v ) {
            update_post_meta( $id, FCP_Forms::$prefix . $_POST['fcp-form-name'] . '_' . $k, $v );
        }
        
    }

    // redirect on success
    $redirect = get_permalink( $id );

}
//*/
