<?php
require_once('Team.php');
require_once('Group.php');

class TeamTable {

    private $teamSymbols = [];
    private $teams = [];
    private $group;

    public function __construct(Group $group, array $teamSymbols = [], array $teams = []) {
        $this->teamSymbols = $teamSymbols;
        $this->teams = $teams;
        $this->group = $group;
    }

    public function sumResults($restrictedTeamSymbols = []) {
        foreach($this->group->getMatches() as $match) {
            $teamA = $match->getTeamA();
            $teamB = $match->getTeamB();

            $a = $teamA->symbol;
            $b = $teamB->symbol;

            if(!empty($restrictedTeamSymbols) && !empty(array_diff([$a, $b], $restrictedTeamSymbols))) {
                continue;
            }
            
            if(!in_array($a, $this->teamSymbols)) {
                $this->teamSymbols[] = $a;
                $this->teams[] = new Team($a);
            }
            if(!in_array($b, $this->teamSymbols)) {
                $this->teamSymbols[] = $b;
                $this->teams[] = new Team($b);
            }

            $this->getTeamBySymbol($a)->addMatchResult($teamA, $teamB);
            $this->getTeamBySymbol($b)->addMatchResult($teamB, $teamA);
        }
    }

    public function calculateOrder(bool $verifyFairPlay = false) {
        $smallTables = [];

        for($i = 0; $i < count($this->teams) - 1; ++$i) {
            for($j = $i + 1; $j < count($this->teams); ++$j) {
                if($i == $j) continue;
                
                $team1 = $this->teams[$i];
                $team2 = $this->teams[$j];
                
                if($team2->points > $team1->points) {
                    $this->swapOrder($i, $j);
                }
                elseif($team2->points == $team1->points) {
                    if($team2->goalsBalance > $team1->goalsBalance) {
                        $this->swapOrder($i, $j);
                    }
                    elseif($team2->goalsBalance == $team1->goalsBalance) {
                        if($team2->scoredGoals > $team1->scoredGoals) {
                            $this->swapOrder($i, $j);
                        }
                        elseif($team2->scoredGoals == $team1->scoredGoals) {
                            if($verifyFairPlay) {
                                if($this->group->getTeamFairPlayResultBySymbol($team2->symbol) > $this->group->getTeamFairPlayResultBySymbol($team1->symbol)) {
                                    $this->swapOrder($i, $j);
                                }
                            }
                            else {
                                $hash = md5($team2->points.$team2->goalsBalance.$team2->scoredGoals);
                                if(!array_key_exists($hash, $smallTables)) {
                                    $smallTables[$hash] = new TeamTable($this->group);
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
                $smallTable->sumResults($smallTable->getTeamSymbols());
                $smallTable->calculateOrder(true);
                
                $this->reorderPartByTable($smallTable);
            }
        }
    }

    public function addTeam(Team $team) {
        $this->teamSymbols[] = $team->symbol;
        $this->teams[] = $team;
    }

    public function getTeamSymbols() {
        return $this->teamSymbols;
    }

    public function getTeams() {
        return $this->teams;
    }

    public function getTeamBySymbol(string $symbol): Team {
        for($i = 0; $i < count($this->teams); ++$i) {
            if($this->teams[$i]->symbol == $symbol) {
                return $this->teams[$i];
            }
        }

        return null;
    }

    public function swapOrder($i, $j) {
        list($this->teamSymbols[$i], $this->teamSymbols[$j]) = [$this->teamSymbols[$j], $this->teamSymbols[$i]];
        list($this->teams[$i], $this->teams[$j]) = [$this->teams[$j], $this->teams[$i]];
    }

    public function reorderPartByTable(TeamTable $table) {
        $newTeamSymbols = [];
        foreach($this->teamSymbols as $symbol) {
            if(in_array($symbol, $table->getTeamSymbols())) {
                $newTeamSymbols = array_merge($newTeamSymbols, $table->getTeamSymbols());
            }
            else {
                $newTeamSymbols[] = $symbol;
            }
        }

        $this->reorderBySymbols(array_values(array_unique($newTeamSymbols)));
    }

    public function print() {
        $i = 1;
        foreach($this->teams as $team) {
            $fairPlayResult = $this->group->getTeamFairPlayResultBySymbol($team->symbol);

            echo ($i++).". $team->symbol - $team->points pkt., $team->scoredGoals-$team->concededGoals ($fairPlayResult)\n";
        }
    }

    private function reorderBySymbols(array $symbols) {
        $this->teamSymbols = $symbols;
        $newTeams = [];

        foreach($symbols as $symbol) {
            $newTeams[] = $this->getTeamBySymbol($symbol);
        }

        $this->teams = $newTeams;
    }
}