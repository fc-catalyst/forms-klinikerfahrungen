<?php

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );

get_header();

include_once ( __DIR__ . '/functions.php' );

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">
    <div class="post-content" itemprop="text">
        <div class="entry-content">

<!-- gutenberg copy start -->

<div class="wp-block-columns alignwide are-vertically-aligned-center fct-entity-hero">

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%">
        <div class="fct-entity-badges">
            <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="Verified" title="Verified" />
            <?php if ( fct1_meta( 'entity-featured' ) ) { ?>
                <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="Featured" title="Featured" />
            <?php } ?>
        </div>
        
        <h1><?php the_title() ?></h1>
        <p><?php fct1_meta_print( 'entity-specialty' ); fct1_meta_print( 'entity-geo-city', false, ' in ' ) ?></p>
        
        <?php if ( method_exists( 'FCP_Comment_Rate', 'print_rating_summary_short' ) ) { ?>
            <?php FCP_Comment_Rate::print_rating_summary_short() ?>
        <?php } ?>
        
        <div class="wp-block-buttons">
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link has-white-color has-text-color" href="#bewertungen">
                    <?php echo wp_count_comments( get_the_ID() )->approved ? 'Bewertungen' : 'Bewerten' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
        <?php if ( $logo = fct1_meta( 'entity-avatar' )[0] ) { ?>
        <div class="fct-entity-photo">
            <?php fct1_image_print( 'entity/' . get_the_ID() . '/' . $logo, [600,600], 0, get_the_title() . ' Logo' ) ?>
        </div>
        <?php } ?>
    </div>

</div>


<div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>

<?php
    $template = [];
    $template[] = 'full';
    
    $countcont = strlen( strip_tags( fct1_meta( 'entity-content' ) ) );
    if ( $countcont < 800 && !fct1_meta( 'entity-tags' ) || $countcont < 400 ) {
        $template[] = 'nocontent';
    }
    
    if ( empty( fct1_meta( 'gallery-images' ) ) ) {
        $template[] = 'nogallery';
    }
    if ( !fct1_meta( 'entity-video' ) ) {
        $template[] = 'novideo';
    }
    
    if ( $template[1] ) {
        unset( $template[0] );
    }

    include_once ( __DIR__ . '/template-parts/' . implode( '-', $template ) . '.php' );
?>

<!-- gutenberg copy end -->

        </div>
    </div>
</article>

<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>

<div class="entry-content" id="bewertungen">
    <?php comments_template() ?>
</div>

<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>

<?php

        if ( $back_img = fct1_meta( 'entity-photo' )[0] ) {
            $back_img = fct1_image_src( 'entity/' . get_the_ID() . '/' . $back_img, [1440,1440], ['center','top'] );
            ?><style>
                .post-<?php the_ID() ?> .fct-entity-hero {
                    --entity-bg:url( '<?php echo $back_img[0] ?>' );
                }
            </style><?php
        }

    endwhile;
endif;

get_footer();
