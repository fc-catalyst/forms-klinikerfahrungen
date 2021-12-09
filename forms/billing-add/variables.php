<?php
/*
 * variables for further use
*/

$time_local = time() + ( $values['entity-timezone-bias'] ? $values['entity-timezone-bias'] : 0 ); // the saved one
$day = 60*60*24;
$prolong_gap = $day*30;