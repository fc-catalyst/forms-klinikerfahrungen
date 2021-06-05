<?php
/*
Process the form data
*/

if ( !is_user_logged_in() ) {
    $warning = 'Please log in to use the form';
    return;
}

// if can create this post type
if ( !get_userdata( wp_get_current_user()->ID )->allcaps['edit_entity'] ) {
    $warning = 'You don\'t have permission to add / edit a clinic';
    return;
}

if ( $warning || !empty( $warns->result ) ) {
    return;
}


// create new post
$id = wp_insert_post( [
    'post_title' => sanitize_text_field( $_POST['entity-name'] ),
    'post_content' => '',
    'post_status' => 'pending',
    'post_author' => wp_get_current_user()->ID,
    'post_type' => 'clinic',
    'comment_status' => 'closed'
]);
// meta boxes are filled automatically with save_post hook

if ( $id === 0 ) {
    $warning = 'Unexpected WordPress error';
    return;
}

/*

// upload files
$dir = wp_get_upload_dir()['basedir'] . '/clinic/' . $id;
if ( !is_dir( $dir ) ) {
    if ( !mkdir( $dir, 0777, true ) ) {
        $warning = 'Can\'t create the folder for the files';
        return;
    }
}

$uploads->add_dirs( [
    'company-logo' => $dir,
    'company-image' => $dir
]);

$uploads->uploaded_files_get();
$uploads->upload();
$uploads->uploaded_files_set();

foreach ( $uploads->uploaded as $v ) {
    update_post_meta( $id, FCP_Forms::$prefix . $_POST['fcp-form-name'] . '_' . $k, $v );
}
//*/

// redirect on success
$redirect = get_permalink( $id );
