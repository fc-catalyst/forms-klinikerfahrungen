<?php

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );

get_header();

include_once ( __DIR__ . '/functions.php' );

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

        $doctor = get_post_type() === 'doctor';
?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope itemtype="https://schema.org/<?php echo $doctor ? 'MedicalOrganization' : 'MedicalClinic' ?>">
    <div class="post-content">
        <div class="entry-content">

<!-- gutenberg copy start -->

<div class="wp-block-columns alignwide are-vertically-aligned-center fct-entity-hero">

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%">
        <div class="fct-entity-badges">
            <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="<?php _e( 'Verified', 'fcpfo-ea' ) ?>" title="<?php _e( 'Verified', 'fcpfo-ea' ) ?>" />
            <?php if ( fct1_meta( 'entity-featured' ) ) { ?>
                <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="<?php _e( 'Featured', 'fcpfo-ea' ) ?>" title="<?php _e( 'Featured', 'fcpfo-ea' ) ?>" />
            <?php } ?>
        </div>
        <h1 itemprop="name"><?php the_title() ?></h1>
        <p><?php
                fct1_meta_print( 'entity-specialty', false, '<span itemprop="medicalSpecialty">', '</span>' ); fct1_meta_print( 'entity-geo-city', false, ' in ' );
        ?></p>
        
        <?php if ( method_exists( 'FCP_Comment_Rate', 'print_rating_summary_short' ) ) { ?>
            <?php FCP_Comment_Rate::print_rating_summary_short() ?>
        <?php } ?>
        
        <?php if ( comments_open() || wp_count_comments( get_the_ID() )->approved ) { ?>
        <div class="wp-block-buttons">
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link has-white-color has-text-color" href="#bewertungen">
                    <?php _e( wp_count_comments( get_the_ID() )->approved ? 'Reviews' : 'Review and Rate', 'fcpcr' ) ?>
                </a>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
        <?php if ( $logo = fct1_meta( 'entity-avatar' )[0] ) { ?>
        <div class="fct-entity-photo">
            <?php fct1_image_print( 'entity/' . get_the_ID() . '/' . $logo, [600,600], 0, get_the_title() . ' ' . __( 'Logo', 'fcpfo-ea' ), $doctor ? 'photo' : 'logo' ) ?>
        </div>
        <?php } ?>
    </div>

</div>


<div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>
<?php
if ( get_the_author_meta( 'ID' ) === get_current_user_id() ) {
?>
<div class="notice"><?php _e( 'Thank you for adding the entry. It will become visible to other visitors as soon as passes the moderation.', 'fcpfo-ea' ) ?></div>
<?php
}
?>

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
<?php

        if ( $back_img = fct1_meta( 'entity-photo' )[0] ) { // ++to image && itemprop
            $back_img = fct1_image_src( 'entity/' . get_the_ID() . '/' . $back_img, [1440,1440], ['center','top'] );
            ?>
            <style>
                .post-<?php the_ID() ?> .fct-entity-hero {
                    --entity-bg:url( '<?php echo $back_img[0] ?>' );
                }
            </style>
            <meta itemprop="image" content="<?php echo $back_img[0] ?>">
            <?php
        }
?>
</article>

<?php echo current_user_can( 'edit_post' ) ? edit_post_link() : '' ?>

<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>

<div class="entry-content" id="bewertungen">
    <?php comments_template() ?>
</div>

<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>

<?php

    endwhile;
endif;

get_footer();
