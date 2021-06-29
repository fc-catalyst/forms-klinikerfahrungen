<?php
/**
 * The template for displaying comments
*/

if ( post_password_required() ) {
	return;
}

?>

<div id="comments" class="comments-area">

	<?php

	if ( have_comments() ) :
	
	$ratings = FCP_Comment_Rate::ratings_count();

    ?>
        <div class="wp-block-columns">
            <div class="wp-block-column comments-list">

                <?php
                    wp_list_comments([
                        'avatar_size' => 80,
                        'max_depth' => 2,
                        'style'       => 'div',
                        'callback' => 'fcp_forms_clinic_comment',
                        'short_ping'  => true,
                        'reply_text'  => 'Reply this review',
                        //'per_page' => 0,
                        //'page' => 1,
                        'reverse_top_level' => true,
                        'reverse_children' => true,
                        'login_text' => 'Login to leave a review / reply'
                    ]);
                ?>
                
                <?php
                the_comments_pagination([
                    'prev_text' => '&lt;&nbsp;prev',
                    'next_text' => 'next&nbsp;&gt;'
                ]);
                ?>

            </div>
            
            <div class="wp-block-column comment-rating-full" style="flex-basis:33.33%">
                <div class="comment-rating-headline with-line">
                    Reviews (<?php echo get_comments_number() ?>)
                </div>
                <div class="comment-rating-total">
                <?php FCP_Comment_Rate::stars_layout( $ratings['__total'] ) ?>
                <?php echo round( $ratings['__total'], 1 ) ?>
                </div>
                <?php FCP_Comment_Rate::nomination_layout( $ratings ) ?>
            </div>

        </div>
        
        <?php

	endif;

	if ( !comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
		?>

		<p class="no-comments"><?php _e( 'Comments are closed.' ) ?></p>

		<?php
	}

	comment_form([
        'title_reply'  => '',
        'title_reply_to' => '',
        'title_reply_before' => '',
        'title_reply_after' => '',
        
        'class_form' => 'comment-form wp-block-columns',
	
        'comment_notes_before' => '<div class="wp-block-column comment-form-fields">
            <h3 id="reply-title" class="comment-reply-title with-line">' . __( 'Leave a Review' ) . '</h3>',
	
        'fields' => [
            'author' => '<p class="comment-form-author">
                <input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . $html_req . ' placeholder="' . __( 'Name' ) . ( $req ? ' *' : '' ) . '" />
            </p>',
            'email'  => '<p class="comment-form-email">
                <input id="email" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" aria-describedby="email-notes"' . $aria_req . $html_req  . ' placeholder="' . __( 'Email' ) . ( $req ? ' *' : '' ) . '" />
            </p>',
            'cookies' => '',
        ],
        'comment_field' => '<p class="comment-form-comment">
            <textarea id="comment" name="comment" cols="45" rows="8" placeholder="' . __( 'The review' ) . '"></textarea>
        </p>
        <p class="comment-form-submit">
            <input type="submit" value="Submit">
        </p>',

        'comment_notes_after' => '</div>',
        'submit_button' => '',
	]);

	?>

</div>

<?php

function fcp_forms_clinic_comment( $comment, $args, $depth ) {
	if ( 'div' === $args['style'] ) {
		$tag       = 'div';
		$add_below = 'comment';
	} else {
		$tag       = 'li';
		$add_below = 'div-comment';
	}

	$classes = ' ' . comment_class( empty( $args['has_children'] ) ? '' : 'parent', null, null, false );
	?>

<<?php echo $tag, $classes; ?> id="comment-<?php comment_ID() ?>">

	<div class="comment-author">
		<?php

		if ( $args['avatar_size'] != 0 ) {
			echo get_avatar( $comment, $args['avatar_size'] );
		}

        echo get_comment_author();

		?>
	</div>

	<?php if ( $comment->comment_approved == '0' ) { ?>
		<em class="comment-awaiting-moderation">
			<?php _e( 'Your comment is awaiting moderation.' ) ?>
		</em><br/>
	<?php } ?>

	<div class="comment-content">
        <?php comment_text() ?>
    </div>

	<div class="comment-more">
		<?php
		comment_reply_link(
			array_merge(
				$args,
				array(
					'add_below' => $add_below,
					'depth'     => $depth,
					'max_depth' => $args['max_depth']
				)
			)
		);

		edit_comment_link( __( 'Edit' ), '  ', '' );
		
		echo get_comment_date();
        //echo get_comment_time();
		?>
	</div>
    <?php
}
