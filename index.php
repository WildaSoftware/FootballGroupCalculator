<?php

$matchesFile = $argv[1];
$matches = json_decode(file_get_contents($matchesFile), true);

$teams = sumResults();
$order = calculateOrder($teams);

$i = 1;
foreach($order as $idx) {
	$team = $teams[$idx];
	
	$name = $team['name'];
	$points = $team['points'];
	$scoredGoals = $team['scoredGoals'];
	$concededGoals = $team['concededGoals'];
	$fairPlay = $team['fairPlay'];
	
	echo ($i++).". $name - $points pkt., $scoredGoals-$concededGoals ($fairPlay)\n";
}

function sumResults($obligatorySymbols = []) {
	global $matches;

	$teams = [];
	foreach($matches as $match) {
		$a = $match['teamA'];
		$b = $match['teamB'];

		if(!empty($obligatorySymbols) && (!in_array($a['symbol'], $obligatorySymbols) || !in_array($b['symbol'], $obligatorySymbols))) {
			continue;
		}
		
		if(!array_key_exists($a['symbol'], $teams)) {
			$teams[$a['symbol']] = ['name' => $a['symbol'], 'points' => 0, 'scoredGoals' => 0, 'concededGoals' => 0, 'goalsBalance' => 0, 'fairPlay' => 0];
		}
		if(!array_key_exists($b['symbol'], $teams)) {
			$teams[$b['symbol']] = ['name' => $b['symbol'], 'points' => 0,'scoredGoals' => 0, 'concededGoals' => 0, 'goalsBalance' => 0, 'fairPlay' => 0];
		}
		
		if($a['goals'] > $b['goals']) {
			$teams[$a['symbol']]['points'] += 3;
		}
		elseif($a['goals'] == $b['goals']) {
			$teams[$a['symbol']]['points']++;
			$teams[$b['symbol']]['points']++;
		}
		else {
			$teams[$b['symbol']]['points'] += 3;
		}
		
		$teams[$a['symbol']]['scoredGoals'] += $a['goals'];
		$teams[$a['symbol']]['concededGoals'] += $b['goals'];
		$teams[$b['symbol']]['scoredGoals'] += $b['goals'];
		$teams[$b['symbol']]['concededGoals'] += $a['goals'];
		
		$teams[$a['symbol']]['goalsBalance'] = $teams[$a['symbol']]['scoredGoals'] - $teams[$a['symbol']]['concededGoals'];
		$teams[$b['symbol']]['goalsBalance'] = $teams[$b['symbol']]['scoredGoals'] - $teams[$b['symbol']]['concededGoals'];
		
		$teams[$a['symbol']]['fairPlay'] += ($a['yellowCards'] * -1) + ($a['doubleYellowCards'] * -3) + ($a['directRedCards'] * -4) + ($a['directYellowAndRedCards'] * -5);
		$teams[$b['symbol']]['fairPlay'] += ($b['yellowCards'] * -1) + ($b['doubleYellowCards'] * -3) + ($b['directRedCards'] * -4) + ($b['directYellowAndRedCards'] * -5);
	}

	return $teams;
}

function calculateOrder($teams, $verifyFairPlay = false) {
	$order = array_keys($teams);
	$smallTables = [];

	for($i = 0; $i < count($order) - 1; ++$i) {
		for($j = $i + 1; $j < count($order); ++$j) {
			if($i == $j) continue;
			
			$team1 = $teams[$order[$i]];
			$team2 = $teams[$order[$j]];
			
			if($team2['points'] > $team1['points']) {
				swapOrder($order, $i, $j);
			}
			elseif($team2['points'] == $team1['points']) {
				if($team2['goalsBalance'] > $team1['goalsBalance']) {
					swapOrder($order, $i, $j);
				}
				elseif($team2['goalsBalance'] == $team1['goalsBalance']) {
					if($team2['scoredGoals'] > $team1['scoredGoals']) {
						swapOrder($order, $i, $j);
					}
					elseif($team2['scoredGoals'] == $team1['scoredGoals']) {
						if($verifyFairPlay) {
							if($team2['fairPlay'] > $team1['fairPlay']) {
								swapOrder($order, $i, $j);
							}
						}
						else {
							$hash = md5($team2['points'].$team2['goalsBalance'].$team2['scoredGoals']);
							if(!array_key_exists($hash, $smallTables)) {
								$smallTables[$hash] = [];
							}
							
							$smallTables[$hash][] = $team1['name'];
							$smallTables[$hash][] = $team2['name'];
						}
					}
				}
			}
		}
	}
	
	if(!empty($smallTables)) {
		foreach($smallTables as $hash => $table) {
			$subTeams = sumResults($table);
			$newOrder = calculateOrder($subTeams, true);
			$smallTables[$hash] = $newOrder;
		}
	}
	
	$result = [];
	$visitedTeams = [];
	for($i = 0; $i < count($order); ++$i) {
		if(in_array($order[$i], $visitedTeams)) continue;
		
		$smallTableIncludingThisTeam = null;
		foreach($smallTables as $smallTable) {
			if(in_array($order[$i], $smallTable)) {
				$smallTableIncludingThisTeam = $smallTable;
				break;
			}
		}
		
		if(!empty($smallTableIncludingThisTeam)) {
			foreach($smallTableIncludingThisTeam as $team) {
				if(in_array($team, $visitedTeams)) continue;
				
				$result[] = $team;
				$visitedTeams[] = $team;
			}
		}
		else {
			$result[] = $order[$i];
			$visitedTeams[] = $order[$i];
		}
	}
	
	return $result;
}

function swapOrder(&$array, $i, $j) {
	list($array[$i], $array[$j]) = [$array[$j], $array[$i]];
}


