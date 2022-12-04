<?php
require_once('Team.php');
require_once('MatchDTO.php');

class Group {

    private $matches;

    public function __construct(string $matchesFile) {
        $this->matches = json_decode(file_get_contents($matchesFile), true);
    }

    public function sumResults($restrictedTeamSymbols = []) {
        $teams = [];
        foreach($this->matches as $match) {
            $matchDto = new MatchDTO($match);

            $teamA = $matchDto->getTeamA();
            $teamB = $matchDto->getTeamB();

            $a = $teamA->symbol;
            $b = $teamB->symbol;

            if(!empty($restrictedTeamSymbols) && !empty(array_diff([$a, $b], $restrictedTeamSymbols))) {
                continue;
            }
            
            if(!array_key_exists($a, $teams)) {
                $teams[$a] = new Team($a);
            }
            if(!array_key_exists($b, $teams)) {
                $teams[$b] = new Team($b);
            }
            
            if($teamA->goals > $teamB->goals) {
                $teams[$a]->points += 3;
            }
            elseif($teamA->goals == $teamB->goals) {
                $teams[$a]->points++;
                $teams[$b]->points++;
            }
            else {
                $teams[$b]->points += 3;
            }
            
            $teams[$a]->scoredGoals += $teamA->goals;
            $teams[$a]->concededGoals += $teamB->goals;
            $teams[$b]->scoredGoals += $teamB->goals;
            $teams[$b]->concededGoals += $teamA->goals;
            
            $teams[$a]->goalsBalance = $teams[$a]->scoredGoals - $teams[$a]->concededGoals;
            $teams[$b]->goalsBalance = $teams[$b]->scoredGoals - $teams[$b]->concededGoals;
            
            $teams[$a]->fairPlay += $teamA->calculateFairPlay();
            $teams[$b]->fairPlay += $teamB->calculateFairPlay();
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