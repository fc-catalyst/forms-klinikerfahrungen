<?php
/*
Print something else instead of the form
*/

if ( ( $atts['override'] === 'logged-in-registered-empty' || $atts['override'] === 'registered-empty' ) && is_user_logged_in() ) {
    $override = '';
    unset( $json->fields );
    return;
}

if ( is_user_logged_in() ) {

    ob_start();
    
    ?>
    <div class="logged-in-message">
    <?php
    
    $roles = wp_get_current_user()->roles;
    if ( in_array( 'entity_delegate', $roles ) || in_array( 'administrator', $roles ) ) {
    
        ?>
        <h2><?php echo sprintf( __( 'Hello, %s', 'fcpfo' ), wp_get_current_user()->display_name ) ?></h2>
        
        <p>Möchten Sie:</p>
        <ul>
            <li>eine neue <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/post-new.php?post_type=clinic' ?>">Klinik</a> oder einen <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/post-new.php?post_type=doctor' ?>">Doktor</a> hinzufügen?</li>
            <li>ihre bestehenden <a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=clinic' ?>">Einträge</a> bearbeiten?</li>
            <li><a href="<?php echo get_edit_profile_url() ?>">ihr Profil bearbeiten?</a></li>
            <li><a href="<?php echo get_option( 'siteurl' ) . '/wp-admin/edit.php?post_type=billing' ?>">ihre Rechnungsdaten aktualisieren?</a></li>
            <li style="text-transform:uppercase"><a href="<?php echo wp_logout_url() ?>"><?php _e( 'Logout', 'fcpfo' ) ?></a></li>
            <li><a href="/kontakt/">unseren Support kontaktieren?</a></li>
        </ul>
        <?php

    } else {
    
        ?>
        <p>
            Hello, <a href="<?php echo get_edit_profile_url() ?>"><?php echo wp_get_current_user()->display_name ?></a>
        </p>
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

    </div>
    <?php
    
    $override = ob_get_contents();
    ob_end_clean();

    unset( $json->fields );

    return;
}
