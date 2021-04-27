<?php

get_header();

?>
	<header>
		<h1><?php single_post_title() ?></h1>
	</header>
<?php

$args = array(
    'post_type'        => 'clinic',
    'orderby'          => 'date',
    'order'            => 'DESC',
//		'posts_per_page'   => '1', // this one is located in vv_addons.php cases_template_pagination() ++ check if it might work now
    'paged'            => get_query_var( 'paged', 1 )
);

// ++ check if normal way of printing is ok

$wp_query = new WP_Query( $args );

if ( $wp_query->have_posts() ) {
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        
?>

    <div class="wp-block-column">
	<article class="post-<?php the_ID(); ?> <?php echo get_post_type(); ?> type-<?php echo get_post_type(); ?> status-publish entry" itemscope="" itemtype="https://schema.org/CreativeWork">
		<header class="entry-header">
			<h2 class="entry-title" itemprop="headline">
				<a class="entry-title-link" rel="bookmark" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>
			<?php get_template_part( 'template-parts/author', 'short' ) ?>
		</header>
		<?php the_excerpt() ?>
	</article>
	</div>

<?php
        
    }
    get_template_part( 'template-parts/pagination' );
}
	

get_footer();
