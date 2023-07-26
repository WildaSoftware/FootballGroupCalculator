<?php

class TeamDTO {

    public $symbol;
    public $goals;
    public $yellowCards;
    public $doubleYellowCards;
    public $directRedCards;
    public $directYellowAndRedCards;
    public $firstGoal;

    public function calculateFairPlay(): int {
        return $this->yellowCards * -1 
            + $this->doubleYellowCards * -3 
            + $this->directRedCards * -4 
            + $this->directYellowAndRedCards * -5;
    }
}

class MatchDTO {

    private $teamA;
    private $teamB;

    public function __construct(array $match) {
        try {
            $t1 = $match['teamA'];
            $t2 = $match['teamB'];

            $this->teamA = new TeamDTO();
            $this->teamA->symbol = $t1['symbol'];
            $this->teamA->goals = $t1['goals'];
            $this->teamA->yellowCards = $t1['yellowCards'];
            $this->teamA->doubleYellowCards = $t1['doubleYellowCards'];
            $this->teamA->directRedCards = $t1['directRedCards'];
            $this->teamA->directYellowAndRedCards = $t1['directYellowAndRedCards'];
            $this->teamA->firstGoal = $t1['firstGoal'];

            $this->teamB = new TeamDTO();
            $this->teamB->symbol = $t2['symbol'];
            $this->teamB->goals = $t2['goals'];
            $this->teamB->yellowCards = $t2['yellowCards'];
            $this->teamB->doubleYellowCards = $t2['doubleYellowCards'];
            $this->teamB->directRedCards = $t2['directRedCards'];
            $this->teamB->directYellowAndRedCards = $t2['directYellowAndRedCards'];
            $this->teamB->firstGoal = $t2['firstGoal'];
        }
        catch(Exception $e) {
            echo $e->getMessage()."\n";
            echo $e->getTraceAsString()."\n";
        }
    }

    public function getTeamA(): TeamDTO {
        return $this->teamA;
    }

    public function getTeamB(): TeamDTO {
        return $this->teamB;
    }
}