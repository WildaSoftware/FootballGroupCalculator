<?php

class TeamComparator {

    private $teamTable;

    public function __construct(TeamTable $teamTable) {
        $this->teamTable = $teamTable;
    }

    public function comparePoints(Team $team1, Team $team2): int {
        if($team2->points == $team1->points) {
            return 0;
        }

        return $team2->points > $team1->points ? 1 : -1;
    }

    public function compareGoalsBalance(Team $team1, Team $team2): int {
        if($team2->goalsBalance == $team1->goalsBalance) {
            return 0;
        }

        return $team2->goalsBalance > $team1->goalsBalance ? 1 : -1;
    }

    public function compareScoredGoals(Team $team1, Team $team2): int {
        if($team2->scoredGoals == $team1->scoredGoals) {
            return 0;
        }

        return $team2->scoredGoals > $team1->scoredGoals ? 1 : -1;
    }

    public function compareFairPlayResults(Team $team1, Team $team2): int {
        $fp1 = $this->teamTable->getGroup()->getTeamFairPlayResultBySymbol($team1->symbol);
        $fp2 = $this->teamTable->getGroup()->getTeamFairPlayResultBySymbol($team2->symbol);

        if($fp2 == $fp1) {
            return 0;
        }

        return $fp2 > $fp1 ? 1 : -1;
    }
}