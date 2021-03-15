<?php
/*
Print instead of the form
*/

return;

if ( !is_user_logged_in() ) {
    return;
}

$user = wp_get_current_user();

ob_start();

?>

<div>
    <?php echo get_avatar( $user->ID ) ?>
    Welcome, <?php echo $user->user_firstname ? $user->user_firstname : $user->user_login ?>
    <a href="<?php echo wp_logout_url( 'index.php' ) ?>">Log out</a>
</div>


<?php

$override = ob_get_contents();
ob_end_clean();
