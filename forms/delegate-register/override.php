<?php
/*
Print something else instead of the form
*/

if ( $attr['override'] == 'registered-empty' && is_user_logged_in() ) {
    $override = '';
    unset( $json->fields );
    return;
}

if ( is_user_logged_in() ) {

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
                <li><a href="<?php echo wp_logout_url() ?>">LOGOUT</a></li>
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
    
    ?>
    <style>
    .logged-in-message {
        padding:25px;
        background:#ffffff26;
    }
    .logged-in-message > *:first-child {
        margin-top:0;
    }
    .logged-in-message * {
        color:#fff;
    }
    </style>
    <?php
    
    $override = ob_get_contents();
    ob_end_clean();

    unset( $json->fields );

    return;
}
