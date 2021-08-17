<?php

// ++ move the functions somewhere, index.php with namespace or a class?

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );


get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">
    <div class="post-content" itemprop="text">
        <div class="entry-content">

<!-- gutenberg copy start -->

<div class="wp-block-columns alignwide are-vertically-aligned-center fcp-entity-hero">

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%">
        <div class="fcp-entity-badges">
            <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="Verified" title="Verified" />
            <?php if ( fct_print_meta( 'entity-featured', true ) ) { ?>
                <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="Featured" title="Featured" />
            <?php } ?>
        </div>
        
        <h1><?php the_title() ?></h1>
        <p><?php fct_print_meta( 'entity-specialty' ); echo ' in '; fct_print_meta( 'entity-geo-city' ) ?></p>
        
        <?php if ( method_exists( 'FCP_Comment_Rate', 'print_rating_summary_short' ) ) { ?>
            <!--<div class="fcp-entity-rating">&#9733;&#9733;&#9733;&#9733;&#9734;<span>5.0</span></div>-->
            <?php FCP_Comment_Rate::print_rating_summary_short() ?>
        <?php } ?>
        
        <div class="wp-block-buttons">
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link has-white-color has-text-color" href="#bewertungen">Bewertungen</a>
            </div>
        </div>
    </div>

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
        <?php if ( $logo = fct_print_meta( 'entity-avatar', true )[0] ) { ?>
        <div class="fcp-entity-photo">
            <img loading="lazy" width="100%" height="100%"
                src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $logo ?>"
                alt="<?php the_title() ?> Logo"
            />
        </div>
        <?php } ?>
    </div>

</div>


<div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>


<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:66.66%">

        <h2>Über</h2>

        <?php fct_print_meta( 'entity-content' ) ?>

        <h2>Unser Behandlungsspektrum</h2>

        <?php fct_print_meta( 'entity-tags', false, '<p>', '</p>' ) ?>

        <div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>
        
        <div class="wp-block-buttons is-content-justification-full">

        <?php if ( $button = fct_print_meta( 'entity-phone', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="tel:<?php echo $button ?>" style="color:var(--h-color)"><strong>Telefon</strong></a></div>
        <?php } ?>
        <?php if ( $button = fct_print_meta( 'entity-email', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="mailto:<?php echo $button ?>" style="color:var(--h-color)"><strong>E-mail</strong></a></div>
        <?php } ?>
        <?php if ( $button = fct_print_meta( 'entity-website', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="<?php echo $button ?>" style="color:var(--h-color)"><strong>Website</strong></a></div>
        <?php } ?>

        </div>
        
        <div class="wp-block-buttons is-content-justification-full">

            <div style="opacity:0.3;" class="wp-block-button is-style-outline fct-button-select"><a class="wp-block-button__link has-text-color" href="#" style="color:var(--h-color)"><strong>Öffnungszeiten</strong></a></div>

        </div>
        
    </div>
    <div class="wp-block-column fcp-vertical-gallery-wrap" style="flex-basis:33.33%">
    
        <h2 class="with-line">Gallerie</h2>

        <div class="fcp-vertical-gallery">
            <?php 
                $gallery = fct_print_meta( 'gallery-images', true );
                if ( !empty( $gallery ) ) {
                    foreach ( $gallery as $v ) {
                    ?>
                        <figure class="wp-block-image">
                            <img loading="lazy" width="562" src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/gallery/' . $v ?>" alt="" />
                        </figure>
                    <?php
                    }
                }
            ?>
        </div>

    </div>
    
</div>

<!--
<div class="wp-block-columns">
    <div class="wp-block-column" style="flex-basis:66.66%">
    
        <p>VIDEO</p>

    </div>
    <div class="wp-block-column" style="flex-basis:33.33%">

        <p>MAP</p>

    </div>
</div>
-->

<!-- gutenberg copy end -->
        </div>
    </div>
</article>

<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>

<div class="entry-content" id="bewertungen">
    <?php comments_template() ?>
</div>
	
<?php

        if ( $back_img = fct_print_meta( 'entity-photo', true )[0] ) {
            ?><style>
                .post-<?php the_ID() ?> .fcp-entity-hero {
                    --entity-bg:url( '<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $back_img ?>' );
                }
            </style><?php
        }

    endwhile;
endif;

get_footer();


function fct_print_meta($name, $return = false, $before = '', $after = '') { // ++ add reset for lists ++ move to common
    static $a = null;
    if ( !$name ) { return ''; }
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
