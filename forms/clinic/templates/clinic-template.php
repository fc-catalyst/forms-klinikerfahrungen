<?php

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

?>

	<article class="post-<?php the_ID(); ?> <?php echo get_post_type(); ?> type-<?php echo get_post_type(); ?> status-publish entry" itemscope="" itemtype="https://schema.org/CreativeWork">
		<div class="post-content" itemprop="text">
            <header class="entry-header wrap-width">
                <h1 class="entry-title" itemprop="headline">
                    Clinic: <?php the_title(); ?>
                </h1>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
		</div>
	</article>
	
<?php

    endwhile;
endif;

get_footer();
