<?php

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );

get_header();

$args = [
    'post_type'        => ['clinic', 'doctor'],
    'orderby'          => 'date',
    'order'            => 'DESC',
    'posts_per_page'   => '12',
    'paged'            => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
    'post_status'      => ['publish', 'private'],
];

$args['meta_query'] = fct_archive_filters();

$wp_query = new WP_Query( $args );


?>
    <div class="wrap-width">
    <?php //the_archive_title( '<h1>', '</h1>' ) ?>
    <h1>Clinics and Doctors</h1>
    
    <?php fct_search_stats( '<p style="margin-top:-25px;opacity:0.45">', '.</p>' ) ?>

    <?php echo do_shortcode('[fcp-form dir="entity-search" notcontent]') ?>
    
<?php

if ( $wp_query->have_posts() ) {
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();

?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">

    <a class="entry-link-cover" rel="bookmark" href="<?php the_permalink(); ?>" title="<?php the_title() ?>"></a>

    <header class="entry-header">
        <div class="entity-badges">
            <img loading="lazy" width="23" height="38" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="Verified" title="Verified" />
            <?php if ( fct1_meta( 'entity-featured' ) ) { ?>
                <img loading="lazy" width="23" height="38" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="Featured" title="Featured" />
            <?php } ?>
        </div>
        <?php if ( $back_img = fct1_meta( 'entity-photo' )[0] ) { ?>
            <div class="entry-photo">
                <?php
                    fct1_image_print(
                        'entity/' . get_the_ID() . '/' . $back_img,
                        [454, 210],
                        ['center', 'top'],
                        get_the_title() . ' Photo'
                    )
                ?>
            </div>
        <?php } ?>
        <h2 class="entry-title" itemprop="headline">
            <a href="<?php the_permalink() ?>"><?php the_title() ?></a>
        </h2>
    </header>
    <div class="entry-details">
        <?php if ( $ava = fct1_meta_print( 'entity-avatar', true )[0] ) { ?>
        <div class="entity-avatar">
            <?php fct1_image_print( 'entity/' . get_the_ID() . '/' . $ava, [74,74], 0, get_the_title() . ' Icon' ) ?>
        </div>
        <?php } ?>
        <div class="entity-about">
            <p>
                <?php fct1_meta_print( 'entity-specialty' ); fct1_meta_print( 'entity-geo-city', false, ' in ' ) ?>
            </p>
            <?php if ( method_exists( 'FCP_Comment_Rate', 'print_stars_total' ) ) { ?>
                <?php FCP_Comment_Rate::print_stars_total() ?>
            <?php } ?>
        </div>
    </div>

</article>


<?php
        
    }
    get_template_part( 'template-parts/pagination' );
    ?></div><!-- /wrap-width --><?php
} else {
    fct_search_stats( '<h2 style="text-align:center">', '</h2>' );
}

?>
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<?php

wp_reset_query();

get_footer();


function fct_archive_filters() {
    global $wpdb;

    $query_meta = [];

    if ( $_GET['place'] ) {
        $val = $wpdb->_real_escape( htmlspecialchars( urldecode( $_GET['place'] ) ) );

        $query_meta[] = [
            'relation' => 'OR',
            [
                'key' => 'entity-region',
                'value' => $val
            ],
            [
                'key' => 'entity-geo-city',
                'value' => $val
            ],
            [
                'key' => 'entity-geo-postcode',
                'value' => $val
            ]
        ];
    }

    if ( $_GET['specialty'] ) {
        $val = $wpdb->_real_escape( htmlspecialchars( urldecode( $_GET['specialty'] ) ) );

        $query_meta[] = [ [
                'key' => 'entity-specialty',
                'value' => $val
            ]
        ];
    }


    if ( count( $query_meta ) > 1 ) {
        $query_meta['relation'] = 'AND';
        return $query_meta;
    }
    
    return $query_meta[0];
}

function fct_search_stats($before = '', $after = '') {
    if ( !$_GET['specialty'] && !$_GET['place'] ) { return; }
    
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $count = $wp_query->post_count . ' results';
    } else {
        $count = 'Nothing';
    }
    
    echo $before .
        $count . ' found' .
        ( $_GET['specialty'] ? ' for <strong>' . $_GET['specialty'] . '</strong>' : '' ) .
        ( $_GET['place'] ? ' in <strong>' . $_GET['place'] . '</strong>' : '' ) .
        $after;
    
}
