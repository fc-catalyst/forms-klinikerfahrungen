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
    $warning = __( 'You don\'t have permission to add / edit a clinic or a doctor', 'fcpfo-ea' );
    return;
}

// upload to tmp dir
if ( !$uploads->upload_tmp() ) {
    return;
}

if ( $warning || !empty( $warns->result ) ) {
    return;
}

// custom $_POST filters

// create new post
$id = wp_insert_post( [
    'post_title' => sanitize_text_field( $_POST['entity-name'] ),
    'post_content' => '',
    'post_status' => 'pending',
    'post_author' => wp_get_current_user()->ID,
    'post_type' => $_POST['entity-entity'], // clinic or doctor
    'comment_status' => 'closed'
]);
// meta boxes are filled automatically with save_post hooked

if ( $id === 0 ) {
    $warning = __( 'Unexpected WordPress error', 'fcpfo' );
    return;
}

// upload files
$dir = wp_get_upload_dir()['basedir'] . '/entity/' . $id;

if ( !$uploads->upload_tmp_main([
    'entity-avatar' => $dir
])) {
    //$redirect = get_edit_post_link( $id, '' );
    return;
}
//print_r( $uploads->warns ); exit;

$update_list = $uploads->format_for_storing();
foreach ( $update_list as $k => $v ) {
    update_post_meta( $id, $k, $v );
}


// if billing exists && is single - attach to the entity
$authors_billing = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => 'billing',
    'posts_per_page' => 2
]);

if ( $authors_billing->post_count === 1 ) {
    update_post_meta( $id, 'entity-billing', $authors_billing->posts[0]->ID );
}

// if billing exists or the tariff is free - redirect to the page, else - redirect to step 3 to create the billing
if ( $authors_billing->post_count > 0 || $_POST['entity-tariff'] === 'kostenloser_eintrag' ) {
    $redirect = get_permalink( $id );
    return;
}

$redirect = $_POST['_wp_http_referer'] ? $_POST['_wp_http_referer'] : get_permalink();
$redirect = add_query_arg( [
    'add_billing' => ''
], $redirect );