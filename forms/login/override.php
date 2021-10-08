<?php
/*
Print something else instead of the form
*/

if ( $atts['override'] == 'logged-in-empty' && is_user_logged_in() ) {
    $override = '';
    unset( $json->fields );
    return;
}

if ( is_user_logged_in() ) {
    $override = '<div class="logged-in-message">
        Hello,
        <a href="' . get_edit_profile_url() . '">
            <strong>' . wp_get_current_user()->display_name . '</strong>
        </a>
    </div>';
    return;
}
