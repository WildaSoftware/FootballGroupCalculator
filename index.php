<?php
require_once('Group.php');

try {
    if(!isset($argv[1])) {
        throw new Exception('No file has been provided');
    }

    $group = new Group($argv[1]);
    $teamTable = $group->prepareTeams();
    $teamTable->calculateOrder();
    $teamTable->print();
}
catch(Exception $e) {
    echo $e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}




