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

// upload to tmp dir
if ( !$uploads->upload_tmp() ) {
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
// meta boxes are filled automatically with save_post hooked

if ( $id === 0 ) {
    $warning = 'Unexpected WordPress error';
    return;
}

// upload files
$dir = wp_get_upload_dir()['basedir'] . '/entity/' . $id;

if ( !$uploads->upload_tmp_main([
    'entity-logo' => $dir,
    'entity-image' => $dir
])) {
    return;
}

$update_list = $uploads->format_for_storing();
foreach ( $update_list as $k => $v ) {
    update_post_meta( $id, $k, $v );
}

// redirect on success
$redirect = get_permalink( $id );
