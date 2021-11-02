<?php
/*
Process the form data
*/

if ( !is_user_logged_in() ) {
    $warning = __( 'Please log in to use the form', 'fcpfo' );
    return;
}

// if can create this post type
if ( !get_userdata( wp_get_current_user()->ID )->allcaps['edit_entities'] ) {
    $warning = __( 'You don\'t have permission to add / edit a billing data', 'fcpfo' );
    return;
}

if ( $warning || !empty( $warns->result ) ) {
    return;
}

// create new post
$title = $_POST['billing-company'] .
    ( $_POST['billing-email'] ? ', ' : '' ) .
    substr( $_POST['billing-email'], 0, strpos( $_POST['billing-email'], '@' ) + 3 ) .
    '…';

$id = wp_insert_post( [
    'post_title' => sanitize_text_field( $title ),
    'post_content' => '',
    'post_status' => 'publish',
    'post_author' => wp_get_current_user()->ID,
    'post_type' => 'billing',
    'comment_status' => 'closed'
]);
// meta boxes are filled automatically with save_post hooked

if ( $id === 0 ) {
    $warning = __( 'Unexpected WordPress error', 'fcpfo' );
    return;
}

// REDIRECT

if ( isset( $_GET['step3'] ) ) {

    // pick the latest entity
    $authors_entitiy = new WP_Query([
        'author' => wp_get_current_user()->ID,
        'post_type' => ['clinic', 'doctor'],
        'orderby' => 'ID',
        'order'   => 'DESC',
        'post_status' => 'any',
        'posts_per_page' => 1,
    ]);
    if (
        $authors_entitiy->post_count > 0 &&
        add_post_meta( $authors_entitiy->posts[0]->ID, 'entity-billing', $id, true )
    ) {
        $redirect = get_permalink( $authors_entitiy->posts[0]->ID ); // the entity post
        return;
    }
}
$redirect = get_edit_post_link( $id, '' ); // edit the billing link