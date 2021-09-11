<?php

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );

get_header();

?>
    <div class="wrap-width">
    <?php //the_archive_title( '<h1>', '</h1>' ) ?>
    <h1>Clinics and Doctors</h1>
<?php

$wp_query = new WP_Query( [
    'post_type'        => ['clinic', 'doctor'],
    'orderby'          => 'date',
    'order'            => 'DESC',
    'posts_per_page'   => '12',
    'paged'            => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
    'post_status'      => ['publish', 'private'], // ++test it
    
]);

if ( $wp_query->have_posts() ) {
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();

        fct_print_meta(); // reset
?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">

    <a class="entry-link-cover" rel="bookmark" href="<?php the_permalink(); ?>" title="<?php the_title() ?>"></a>

    <header class="entry-header">
        <div class="entity-badges">
            <img loading="lazy" width="23" height="38" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="Verified" title="Verified" />
            <?php if ( fct_print_meta( 'entity-featured', true ) ) { ?>
                <img loading="lazy" width="23" height="38" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="Featured" title="Featured" />
            <?php } ?>
        </div>
        <?php if ( $back_img = fct_print_meta( 'entity-photo', true )[0] ) { ?>
            <div class="entry-photo">
                <img loading="lazy" width="100%" height="100%"
                    src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $back_img ?>"
                    alt=""
                />
            </div>
        <?php } ?>
        <h2 class="entry-title" itemprop="headline">
            <a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title() ?></a>
        </h2>
    </header>
    <div class="entry-details">
        <?php if ( $logo = fct_print_meta( 'entity-avatar', true )[0] ) { ?>
        <div class="entity-avatar">
            <img loading="lazy" width="100%" height="100%"
                src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $logo ?>"
                alt="<?php the_title() ?> <?php echo get_post_type() == 'clinic' ? 'Logo' : 'Photo' ?>"
            />
        </div>
        <?php } ?>
        <div class="entity-about">
            <p>
                <?php fct_print_meta( 'entity-specialty' ); fct_print_meta( 'entity-geo-city', false, ' in ' ) ?>
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
    ?></div><?php
}

get_footer();

function fct_print_meta($name = '', $return = false, $before = '', $after = '') {
    static $a = null;
    if ( !$name ) { $a = null; return; }
    if ( $a === null ) {
        $a = get_post_meta( get_the_ID(), '' );
    }

    if ( is_serialized( $a[ $name ][0] ) ) {
        $result = unserialize( $a[ $name ][0] );
    } else {
        $result = trim( $a[ $name ][0] ) ? $before . $a[ $name ][0] . $after : '';
    }

    if ( $return ) {
        return $result;
    }
    echo $result;
}
