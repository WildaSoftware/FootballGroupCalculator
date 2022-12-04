<?php

require_once('MatchDTO.php');

class Team {

    private $attributes = [];

    public function __construct(string $symbol) {
        $this->symbol = $symbol;
    }

    public function __set($name, $value) {
        if(!in_array($name, ['symbol', 'points', 'scoredGoals', 'concededGoals', 'goalsBalance', 'fairPlay'])) {
            throw new \Exception("There is no \"$name\" attribute to set in Team");
        }

        $this->attributes[$name] = $value;
    }

    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    public function addMatchResult(TeamDTO $myTeam, TeamDto $opponent) {
        if($myTeam->goals > $opponent->goals) {
            $this->points += 3;
        }
        elseif($myTeam->goals == $opponent->goals) {
            $this->points++;
        }
        
        $this->scoredGoals += $myTeam->goals;
        $this->concededGoals += $opponent->goals;
        
        $this->goalsBalance = $this->scoredGoals - $this->concededGoals;
        
        $this->fairPlay += $myTeam->calculateFairPlay();
    }
}