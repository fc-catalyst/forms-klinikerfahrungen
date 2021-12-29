<?php

$mailing['domain'] = $_SERVER['SERVER_NAME']; // ++send these to global vars, or find similar in wp
$mailing = [
    'url' => 'http'.( !empty( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off' ) ? 's' : '' ).'://'.$mailing['domain'].'/',
    'sending' => 'robot@'.$mailing['domain'],
    'sending_name' => 'Klinikerfahrungen.de',
    'accountant' => 'finnish.ru@gmail.com',//'buchhaltung@firmcatalyst.com',
    'domain' => $mailing['domain'],
];

$day = 60 * 60 * 24;
$prolong_gap = $day * 14;
$billed_flush_gap = $day * 30;
$requested_flush_gap = $day * 60; // just a special case, where forgot to bill