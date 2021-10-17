<?php
/*
Print something else instead of the form
*/

if ( !is_user_logged_in() ) {
    unset( $json->fields );
    $override = '';
    return;
}

// autofill some values

// pick the newes entity meta
$wp_query = new WP_Query([
    'author' => wp_get_current_user()->ID,
    'post_type' => ['clinic', 'doctor'],
    'orderby' => 'ID',
    'order'   => 'DESC',
    'post_status' => 'any',
    'posts_per_page' => 1,
]);
// ++ sanitize values before printing to input
//billing-company - get_the_title()
$autofill = [
    'billing-address' => 'entity-address',
    'billing-region' => 'entity-region',
    'billing-city' => 'entity-geo-city',
    'billing-postcode' => 'entity-geo-postcode',
    'billing-email' => 'entity-email',
];

if ( $wp_query->have_posts() ) {
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();

        $json->fields = FCP_Forms::json_change_field( $json->fields,
            'billing-company',
            'value',
            get_the_title()
        );

        foreach( $autofill as $k => $v ) {
            $json->fields = FCP_Forms::json_change_field( $json->fields,
                $k,
                'value',
                fct1_meta_print( $v, true )
            );
        }

        break;
    }
}
wp_reset_query();