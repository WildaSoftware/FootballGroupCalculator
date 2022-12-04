<?php
require_once('Team.php');

class Group {

    private $matches;

    public function __construct(string $matchesFile) {
        $this->matches = json_decode(file_get_contents($matchesFile), true);
    }

    public function sumResults($obligatorySymbols = []) {
        $teams = [];
        foreach($this->matches as $match) {
            $a = $match['teamA'];
            $b = $match['teamB'];
    
            if(!empty($obligatorySymbols) && (!in_array($a['symbol'], $obligatorySymbols) || !in_array($b['symbol'], $obligatorySymbols))) {
                continue;
            }
            
            if(!array_key_exists($a['symbol'], $teams)) {
                $teams[$a['symbol']] = new Team($a['symbol']);
            }
            if(!array_key_exists($b['symbol'], $teams)) {
                $teams[$b['symbol']] = new Team($b['symbol']);
            }
            
            if($a['goals'] > $b['goals']) {
                $teams[$a['symbol']]->points += 3;
            }
            elseif($a['goals'] == $b['goals']) {
                $teams[$a['symbol']]->points++;
                $teams[$b['symbol']]->points++;
            }
            else {
                $teams[$b['symbol']]->points += 3;
            }
            
            $teams[$a['symbol']]->scoredGoals += $a['goals'];
            $teams[$a['symbol']]->concededGoals += $b['goals'];
            $teams[$b['symbol']]->scoredGoals += $b['goals'];
            $teams[$b['symbol']]->concededGoals += $a['goals'];
            
            $teams[$a['symbol']]->goalsBalance = $teams[$a['symbol']]->scoredGoals - $teams[$a['symbol']]->concededGoals;
            $teams[$b['symbol']]->goalsBalance = $teams[$b['symbol']]->scoredGoals - $teams[$b['symbol']]->concededGoals;
            
            $teams[$a['symbol']]->fairPlay += ($a['yellowCards'] * -1) + ($a['doubleYellowCards'] * -3) + ($a['directRedCards'] * -4) + ($a['directYellowAndRedCards'] * -5);
            $teams[$b['symbol']]->fairPlay += ($b['yellowCards'] * -1) + ($b['doubleYellowCards'] * -3) + ($b['directRedCards'] * -4) + ($b['directYellowAndRedCards'] * -5);
        }
    
        return $teams;
    }
    
    public function calculateOrder($teams, $verifyFairPlay = false) {
        $order = array_keys($teams);
        $smallTables = [];
    
        for($i = 0; $i < count($order) - 1; ++$i) {
            for($j = $i + 1; $j < count($order); ++$j) {
                if($i == $j) continue;
                
                $team1 = $teams[$order[$i]];
                $team2 = $teams[$order[$j]];
                
                if($team2->points > $team1->points) {
                    $this->swapOrder($order, $i, $j);
                }
                elseif($team2->points == $team1->points) {
                    if($team2->goalsBalance > $team1->goalsBalance) {
                        $this->swapOrder($order, $i, $j);
                    }
                    elseif($team2->goalsBalance == $team1->goalsBalance) {
                        if($team2->scoredGoals > $team1->scoredGoals) {
                            $this->swapOrder($order, $i, $j);
                        }
                        elseif($team2->scoredGoals == $team1->scoredGoals) {
                            if($verifyFairPlay) {
                                if($team2->fairPlay > $team1->fairPlay) {
                                    $this->swapOrder($order, $i, $j);
                                }
                            }
                            else {
                                $hash = md5($team2->points.$team2->goalsBalance.$team2->scoredGoals);
                                if(!array_key_exists($hash, $smallTables)) {
                                    $smallTables[$hash] = [];
                                }
                                
                                $smallTables[$hash][] = $team1->symbol;
                                $smallTables[$hash][] = $team2->symbol;
                            }
                        }
                    }
                }
            }
        }
        
        if(!empty($smallTables)) {
            foreach($smallTables as $hash => $table) {
                $subTeams = $this->sumResults($table);
                $newOrder = $this->calculateOrder($subTeams, true);
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

    public function printResults($teams, $order) {
        $i = 1;
        foreach($order as $idx) {
            $team = $teams[$idx];
            echo ($i++).". $team->symbol - $team->points pkt., $team->scoredGoals-$team->concededGoals ($team->fairPlay)\n";
        }
    }
    
    private function swapOrder(&$array, $i, $j) {
        list($array[$i], $array[$j]) = [$array[$j], $array[$i]];
    }
}