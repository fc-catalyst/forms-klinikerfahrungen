<?php
/*
Process meta boxes data
*/

// the billing method must belong to the delegate
$wp_query = new WP_Query([
    'author'         => $post->post_author,
    'post_type'      => 'billing',
    'p'              => $_POST['entity-billing'],
    'posts_per_page' => 1,
    'post_status'      => ['publish', 'private'],
]);

if ( !$wp_query->have_posts() ) {
    $_POST['entity-billing'] = '';
}