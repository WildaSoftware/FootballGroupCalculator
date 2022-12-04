<?php
require_once('Team.php');
require_once('MatchDTO.php');
require_once('TeamTable.php');

class Group {

    private $matches;
    private $teamFairPlayResults;

    public function __construct(string $matchesFile) {
        if(!file_exists($matchesFile)) {
            throw new Exception("Provided matches files \"$matchesFile\" does not exist");
        }

        $fileContent = file_get_contents($matchesFile);
        if(!$fileContent) {
            throw new Exception("Provided matches files \"$matchesFile\" cannot be read");
        }

        $this->matches = json_decode($fileContent, true);
        $this->teamFairPlayResults = [];
    }

    public function sumResults($restrictedTeamSymbols = []): TeamTable {
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

            $teams[$a]->addMatchResult($teamA, $teamB);
            $teams[$b]->addMatchResult($teamB, $teamA);
        }

        if(empty($restrictedTeamSymbols)) {
            foreach($teams as $symbol => $team) {
                $this->teamFairPlayResults[$symbol] = $team->fairPlay;
            }
        }
    
        return new TeamTable(array_keys($teams), array_values($teams));
    }
    
    public function calculateOrder(TeamTable $table, bool $verifyFairPlay = false) {
        $smallTables = [];

        for($i = 0; $i < count($table->getTeams()) - 1; ++$i) {
            for($j = $i + 1; $j < count($table->getTeams()); ++$j) {
                if($i == $j) continue;
                
                $team1 = $table->getTeams()[$i];
                $team2 = $table->getTeams()[$j];
                
                if($team2->points > $team1->points) {
                    $table->swapOrder($i, $j);
                }
                elseif($team2->points == $team1->points) {
                    if($team2->goalsBalance > $team1->goalsBalance) {
                        $table->swapOrder($i, $j);
                    }
                    elseif($team2->goalsBalance == $team1->goalsBalance) {
                        if($team2->scoredGoals > $team1->scoredGoals) {
                            $table->swapOrder($i, $j);
                        }
                        elseif($team2->scoredGoals == $team1->scoredGoals) {
                            if($verifyFairPlay) {
                                if($this->teamFairPlayResults[$team2->symbol] > $this->teamFairPlayResults[$team1->symbol]) {
                                    $table->swapOrder($i, $j);
                                }
                            }
                            else {
                                $hash = md5($team2->points.$team2->goalsBalance.$team2->scoredGoals);
                                if(!array_key_exists($hash, $smallTables)) {
                                    $smallTables[$hash] = new TeamTable();
                                }
                                
                                $smallTables[$hash]->addTeam($team1);
                                $smallTables[$hash]->addTeam($team2);
                            }
                        }
                    }
                }
            }
        }
        
        if(!empty($smallTables)) {
            foreach($smallTables as $hash => $smallTable) {
                $restrictedTable = $this->sumResults($smallTable->getTeamSymbols());
                $orderedSmallTable = $this->calculateOrder($restrictedTable, true);
                
                $table->reorderPartByTable($orderedSmallTable);
            }
        }

        return $table;
    }
}