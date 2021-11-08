<?php
/*
Modify the values before printing to inputs
*/

// collect the attached entities
$author = get_post( $_GET['post'] );

if ( $author->post_type !== 'billing' ) { return; } //++ move it to the class

/*
$wp_query = new WP_Query([
    'author' => $author->post_author,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'post_title',
    'order'   => 'ASC',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'entity-billing',
            'value' => $_GET['post']
        ]
    ],
]);

if ( !$wp_query->have_posts() ) { return; }

$entities = [];
while ( $wp_query->have_posts() ) {
    $wp_query->the_post();
    $entities[] = get_the_title() .
        ' <a href="'.get_the_permalink().'">' . __( 'View' ) . '</a>' .
        ' <a href="'.get_edit_post_link().'">' . __( 'Edit' ) . '</a>'
    ;
}
wp_reset_postdata(); // that's the reason
wp_reset_query(); // that's the reason
//*/
$entity_posts = get_posts([
    'author' => $author->post_author,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'post_title',
    'order'   => 'ASC',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'entity-billing',
            'value' => $_GET['post']
        ]
    ],
]);
$entities = [];
foreach( $entity_posts as $epost ){
	setup_postdata( $epost );
    $entities[] = get_the_title() .
        ' <a href="'.get_the_permalink().'">' . __( 'View' ) . '</a>' .
        ' <a href="'.get_edit_post_link().'">' . __( 'Edit' ) . '</a>'
    ;
}
wp_reset_postdata();

if ( !$entities[0] ) { return; }

array_push( $this->s->fields, (object) [
        'type' => 'notice',
        'text' => '<p>Clinics and Doctors assigned:</p><p>' . implode( '<br>', $entities ) . '</p>',
        'meta_box' => true,
]);

// FCP_Forms::json_change_field( $this->s->fields, 'billing-name', 'placeholder', 'Ahaha' ); // this just works XDD
// $values['billing-name'] = 'Always'; // this works too!!