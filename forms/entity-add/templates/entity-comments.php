<?php
/**
 * The template for displaying comments
*/

if ( post_password_required() ) { return; }
if ( !comments_open() && !get_comments_number() || !post_type_supports( get_post_type(), 'comments' ) ) { return; }

?>
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<div id="comments" class="comments-area entry-content">

    <?php if ( have_comments() ) { ?>

        <div class="wp-block-columns">
            <div class="wp-block-column">
                <ul class="comments-list">
                    <?php wp_list_comments() ?>
                </ul>
                <?php the_comments_pagination(); ?>
            </div>
            <?php
            if ( method_exists( 'FCP_Comment_Rate', 'summary_print' ) ) {
                ?>
                <div class="wp-block-column" style="flex-basis:33.33%">
                    <?php FCP_Comment_Rate::summary_print() ?>
                </div>
                <?php
            }
            ?>
        </div>

    <?php } ?>

    <?php
    // print the form
    if ( comments_open() ) { comment_form(); }
    ?>

</div>

<?php