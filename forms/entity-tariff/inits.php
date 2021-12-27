<?php

$mailing = [
    'url' => 'https://klinikerfahrungen.de/', //++just lazy, use a global var later or a function
    'sending' => 'robot@klinikerfahrungen.de',
    'sending_name' => 'Klinikerfahrungen.de',
    'accountant' => 'buchhaltung@firmcatalyst.com',
];

$day = 60 * 60 * 24;
$prolong_gap = $day * 14;
$billed_flush_gap = $day * 30;
$requested_flush_gap = $day * 60; // just a special case, where forgot to bill