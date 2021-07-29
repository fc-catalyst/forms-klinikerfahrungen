<?php
/*
Process the form data
*/

if ( !is_user_logged_in() ) {
    $warning = 'Please log in to use the form';
    return;
}

// if can create this post type
if ( !get_userdata( wp_get_current_user()->ID )->allcaps['edit_entities'] ) {
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

// autofill the entity billing information
// picking the new clinic (the only, actually)
$authors_entitiy = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'ID',
    'order'   => 'DESC',
    'post_status' => 'any',
    'posts_per_page' => 2,
]);

// checking if there is only one billing method (the just added one)
$authors_billing = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => 'billing',
    'posts_per_page' => 2
]);

if ( $authors_entitiy->post_count === 1 && $authors_billing->post_count === 1 ) {
    update_post_meta( $authors_entitiy->posts[0]->ID, 'entity-billing', $id );
    $redirect = get_permalink( $authors_entitiy->posts[0]->ID );
    return;
}

// redirect to the billing post editor
$redirect = get_edit_post_link( $id, '' );
