<?php

// include autocompletes
wp_enqueue_style(
    'fcp-forms-autocomplete-advisor',
    $this->self_path . '/assets/autocomplete-advisor.css',
    ['fcp-forms'],
    '1.0.1' . ( FCP_Forms::$dev ? '.'.time() : '' )
);

wp_enqueue_script(
    'fcp-forms-autocomplete-advisor',
    $this->self_path . '/assets/autocomplete-advisor.js',
    ['jquery', 'fcp-forms'],
    '1.0.1' . ( FCP_Forms::$dev ? '.'.time() : '' )
);
