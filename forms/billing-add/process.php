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
    $warning = 'You don\'t have permission to add / edit a billing data';
    return;
}

if ( $warning || !empty( $warns->result ) ) {
    return;
}

// create new post
$id = wp_insert_post( [
    'post_title' => sanitize_text_field( $_POST['billing-company'] ),
    'post_content' => '',
    'post_status' => 'publish',
    'post_author' => wp_get_current_user()->ID,
    'post_type' => 'billing',
    'comment_status' => 'closed'
]);
// meta boxes are filled automatically with save_post hooked

if ( $id === 0 ) {
    $warning = 'Unexpected WordPress error';
    return;
}

// redirect on success
$redirect = get_permalink( $id );
