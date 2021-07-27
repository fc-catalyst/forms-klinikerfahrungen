<?php

// ++ move the functions somewhere, index.php with namespace or a class?

$fcp_imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );

function fcp_epmp($name, $return = '') { // ++ add reset for lists
    static $pmb = null;
    //if ( !$name ) { return ''; }
    if ( $pmb === null ) {
        $pmb = get_post_meta( get_the_ID() );
    }
    $result = $pmb[ 'fcpf_' . $name ] ? $pmb[ 'fcpf_' . $name ][0] : '';
    if ( $return ) {
        return $result;
    }
    echo $result;
}

function fcp_eimgsrc($name, $return = '') {
    $result = wp_get_upload_dir()['url'] . '/clinic/' . get_the_ID() . '/' . $name;
    if ( $return ) {
        return $result;
    }
    echo $result;
}

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-publish entry" itemscope="" itemtype="https://schema.org/CreativeWork">
    <div class="post-content" itemprop="text">
        <div class="entry-content">
            <?php // the_content() ?>

<!-- gutenberg copy start -->

<div class="wp-block-columns alignwide are-vertically-aligned-center fcp-clinic-hero">

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%">
        <div class="fcp-clinic-badges">
            <img loading="lazy" width="46" height="76" src="<?php echo $fcp_imgs_dir . 'badge-1.png' ?>" alt="Featured" />
            <img loading="lazy" width="46" height="76" src="<?php echo $fcp_imgs_dir . 'badge-1.png' ?>" alt="Featured" />
        </div>
        
        <h1><?php the_title() ?></h1>
        <p>Where to take it from?? Plastische und Ästhetische Chirurgen</p>
        
        <div class="fcp-clinic-rating">&#9733;&#9733;&#9733;&#9733;&#9734;<span>5.0</span></div>
        
        <div class="wp-block-buttons">
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link has-white-color has-text-color" href="#bewertungen">Bewertungen</a>
            </div>
        </div>
    </div>

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
        <?php if ( $logo = fcp_epmp( 'entity-avatar', true ) ) { ?>
        <div class="fcp-clinic-photo">
            <img loading="lazy" width="100%" height="100%"
                src="<?php echo $logo ?>"
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

        <?php fcp_epmp( 'entity-description' ) ?>
        The content is missing for now :(

        <h2>Unser Behandlungsspektrum</h2>

        <p><?php fcp_epmp( 'entity-tags' ) ?></p>

        <div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>
        
        <div class="wp-block-buttons is-content-justification-full">

        <?php if ( $button = fcp_epmp( 'entity-phone', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="tel:<?php echo $button ?>" style="color:var(--h-color)"><strong>Telefon</strong></a></div>
        <?php } ?>
        <?php if ( $button = fcp_epmp( 'entity-email', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="mailto:<?php echo $button ?>" style="color:var(--h-color)"><strong>E-mail</strong></a></div>
        <?php } ?>
        <?php if ( $button = fcp_epmp( 'entity-website', true ) ) { ?>
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
                $gallery = fcp_epmp( 'entity-gallery', true );
                if ( $gallery && !empty( $gallery ) ) {
                    $gallery = unserialize( $gallery );
                    foreach ( $gallery as $v ) {
                    ?>
                        <figure class="wp-block-image"><img loading="lazy" width="562" src="<?php echo $v ?>" alt="" /></figure>
                    <?php
                    }
                }
            ?>
<!--
            <figure class="wp-block-image"><img loading="lazy" width="562" height="471" src="http://localhost/wordpress/wp-content/uploads/Leo-034712.jpg" alt="" /></figure>

            <figure class="wp-block-image"><img loading="lazy" width="479" height="549" src="http://localhost/wordpress/wp-content/uploads/Japan2.jpg" alt="" /></figure>

            <figure class="wp-block-image"><img loading="lazy" width="749" height="1022" src="http://localhost/wordpress/wp-content/uploads/nathan-dumlao-Wr3comVZJxU-unsplash-1.png" alt="" /></figure>
-->
        </div>

    </div>
    
</div>


<div class="wp-block-columns">
    <div class="wp-block-column" style="flex-basis:66.66%">
    
        <p>VIDEO</p>

    </div>
    <div class="wp-block-column" style="flex-basis:33.33%">

        <p>MAP</p>

    </div>
</div>


<!-- gutenberg copy end -->
        </div>
    </div>
</article>

<div class="entry-content">
    <?php comments_template() ?>
</div>
	
<?php

        if ( $back_img = fcp_epmp( 'entity-image', true ) ) {
            ?><style>
                .post-<?php the_ID() ?> .fcp-clinic-hero {
                    --clinic-bg:url( '<?php echo $back_img ?>' );
                }
            </style><?php
        }

    endwhile;
endif;

get_footer();
