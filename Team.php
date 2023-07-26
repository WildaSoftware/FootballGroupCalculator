<?php

require_once('MatchDTO.php');

class Team
{

    private $attributes = [];

    public function __construct($symbol)
    {
        $this->symbol = $symbol;
    }

    public function __set($symbol, $value)
    {
        if (!in_array($symbol, ['symbol', 'points', 'scoredGoals', 'concededGoals', 'goalsBalance', 'fairPlay', 'fastestGoal'])) {
            throw new \Exception("There is no \"$symbol\" attribute to set in Team");
        }

        $this->attributes[$symbol] = $value;
    }

    public function __get($symbol)
    {
        return $this->attributes[$symbol] ?? null;
    }

    public function addMatchResult(TeamDTO $myTeam, TeamDto $opponent)
    {
        if ($myTeam->goals > $opponent->goals) {
            $this->points += 3;
        } elseif ($myTeam->goals == $opponent->goals) {
            $this->points++;
        }

        $this->scoredGoals += $myTeam->goals;
        $this->concededGoals += $opponent->goals;

        $this->goalsBalance = $this->scoredGoals - $this->concededGoals;

        $this->fairPlay += $myTeam->calculateFairPlay();

        if ($myTeam->firstGoal != null) {
            $timeToSeconds = function ($time) {
                list($minutes, $seconds) = explode(':', $time);
                return ($minutes * 60) + $seconds;
            };

            $time1 = $timeToSeconds($myTeam->firstGoal);

            if ($this->fastestGoal === null) {
                $this->fastestGoal = $myTeam->firstGoal;
            } else {
                $time2 = $timeToSeconds($this->fastestGoal);

                if ($time1 < $time2) $this->fastestGoal = $myTeam->firstGoal;
            }
        }
    }
}
