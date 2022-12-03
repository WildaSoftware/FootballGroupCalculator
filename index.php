<?php
require_once('Group.php');

$group = new Group($argv[1]);

$teams = $group->sumResults();
$order = $group->calculateOrder($teams);
$group->printResults($teams, $order);




