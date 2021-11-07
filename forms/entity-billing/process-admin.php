<?php
/*
Process meta boxes data
*/

// the billing method must belong to the delegate
$args = [
    'author'         => $post->post_author,
    'post_type'      => 'billing',
    'p'              => $_POST['entity-billing'],
    'posts_per_page' => 1,
    'post_status'      => ['publish', 'private'],
];

$wp_query = new WP_Query( $args );

if ( !$wp_query->have_posts() ) {
    $_POST['entity-billing'] = '';
}