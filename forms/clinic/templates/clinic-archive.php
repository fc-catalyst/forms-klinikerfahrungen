<?php

get_header();

function fcp_epmp($name, $return = '') { // ++ add reset for lists
    static $pmb = null, $id = 0;
    //if ( !$name ) { return ''; }
    if ( $pmb === null || $id !== get_the_ID() ) {
        $pmb = get_post_meta( get_the_ID() );
        $id = get_the_ID();
    }
    $result = $pmb[ 'fcpf_' . $name ] ? $pmb[ 'fcpf_' . $name ][0] : '';
    if ( $return ) {
        return $result;
    }
    echo $result;
}

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

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-publish entry" itemscope="" itemtype="https://schema.org/CreativeWork">
    <div class="post-content" itemprop="text">
        <div class="entry-content">

<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:33.33%">
        <?php if ( $logo = fcp_epmp( 'entity-avatar', true ) ) { ?>
        <a class="entry-title-link" rel="bookmark" href="<?php the_permalink(); ?>">
            <img loading="lazy" width="100%" height="100%"
                src="<?php echo $logo ?>"
                alt="<?php the_title() ?> Logo"
            />
        </a>
        <?php } ?>
    </div>
    <div class="wp-block-column" style="flex-basis:66.66%">
        <h2 class="entry-title" itemprop="headline">
            <a class="entry-title-link" rel="bookmark" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        content is missing :(
    </div>
</div>

<div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>

<?php
        
    }
    get_template_part( 'template-parts/pagination' );
}
	

get_footer();
