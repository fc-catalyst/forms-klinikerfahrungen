<?php
/*
Print something else instead of the form
*/

if ( is_user_logged_in() ) {
    if ( !is_home() ) {
        $override = '';
        unset( $json->fields );
        return;        
    }

    ob_start();
    
    $roles = wp_get_current_user()->roles;
    if ( in_array( 'entity_delegate', $roles ) || in_array( 'administrator', $roles ) ) {
    
    ?>
    <div class="logged-in-message">
        <h2>Hello, <?php echo wp_get_current_user()->display_name ?></h2>
        
        <p>Would you like to:</p>
        <ul>
            <li>add a new <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/post-new.php?post_type=clinic' ?>">clinic</a> or a <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/post-new.php?post_type=doctor' ?>">doctor</a>?</a></li>
            <li>manage your existing <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic' ?>">clinics</a> and <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=doctor' ?>">doctors</a>?</a></li>
            <li><a href="<?php echo get_edit_profile_url() ?>">manage your profile?</a></li>
            <li><a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=billing' ?>">add or manage your billing information?</a></li>
        </ul>
    </div>
    <?php

    } else {
    
    ?>
    <div class="logged-in-message">
        <p>
            Hello, <a href="<?php echo get_edit_profile_url() ?>"><?php echo wp_get_current_user()->display_name ?></a>
        </p>
    </div>
    <?php
    
    }
    $override = ob_get_contents();
    ob_end_clean();

    unset( $json->fields );

    return;
}
