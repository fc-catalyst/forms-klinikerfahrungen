<?php
/*
Print something else instead of the form
*/

if ( !is_user_logged_in() ) {
    unset( $json->fields );
    $override = '';
    return;
}
/*
// autofill
// pick the newes entity meta
$authors_entitiy = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'ID',
    'order'   => 'DESC',
    'post_status' => 'any',
    'posts_per_page' => 2,
]);
if ( $authors_entitiy->post_count === 1 ) {
    //$authors_entitiy->posts[0]->ID
}

foreach ( $json->fields as $k => $v ) { 
    if ( $v->name == 'specialty' && $_GET['specialty'] ) {
        $json->fields[$k]->value = htmlspecialchars( urldecode( $_GET['specialty'] ) );
    }
    if ( $v->name == 'place' && $_GET['place'] ) {
        $json->fields[$k]->value = htmlspecialchars( urldecode( $_GET['place'] ) );
    }
}
//*/
