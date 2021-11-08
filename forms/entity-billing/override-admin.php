<?php
/*
Modify the values before printing to inputs
*/

// collect the attached entities
$author = get_post( $_GET['post'] );

if ( !in_array( $author->post_type, ['clinic', 'doctor'] ) ) { return; } //++ move it to the class

// get the billing options
$billing_posts = get_posts([
    'author' => $author->post_author,
    'post_type' => 'billing',
    'orderby' => 'post_title',
    'order'   => 'ASC',
    'post_status' => 'any',
    'posts_per_page' => -1,
]);

$billings = [];
foreach( $billing_posts as $post ){
	setup_postdata( $post );
    $billings[ get_the_ID() ] = get_the_title();
}
wp_reset_postdata();

if ( empty( $billings) ) {
    FCP_Forms::json_change_field( $this->s->fields, 'entity-billing', 'type', 'notice' );
    FCP_Forms::json_change_field( $this->s->fields, 'entity-billing', 'text',
        '<a href="/wp-admin/post-new.php?post_type=billing" target="_blank">' . __( 'Add New Billing', 'fcpfo' ) . '</a>'
    );
    return;
}

FCP_Forms::json_change_field( $this->s->fields, 'entity-billing', 'options', $billings );