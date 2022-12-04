<?php
require_once('Group.php');

try {
    if(!isset($argv[1])) {
        throw new Exception('No file has been provided');
    }

    $group = new Group($argv[1]);
    $teams = $group->sumResults();
    $order = $group->calculateOrder($teams);
    $group->printResults($teams, $order);
}
catch(Exception $e) {
    echo $e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}




