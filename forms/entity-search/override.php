<?php
/*
Print something else instead of the form
*/

// fill in the search values to inputs
if ( !$_GET['specialty'] && !$_GET['place'] ) { return; }

$json->fields = FCP_Forms::json_change_field(
    $json->fields,
    'specialty',
    'value',
    $_GET['specialty'] ? htmlspecialchars( urldecode( $_GET['specialty'] ) ) : ''
);

$json->fields = FCP_Forms::json_change_field(
    $json->fields,
    'place',
    'value',
    $_GET['place'] ? htmlspecialchars( urldecode( $_GET['place'] ) ) : $_GET['place']
);