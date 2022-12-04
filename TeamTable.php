<?php
require_once('Team.php');

class TeamTable {

    private $teamSymbols = [];
    private $teams = [];

    public function __construct(array $teamSymbols = [], array $teams = []) {
        $this->teamSymbols = $teamSymbols;
        $this->teams = $teams;
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
        for($i = 0; $i < count($this->teamSymbols); ++$i) {
            if($this->teamSymbols[$i] == $symbol) {
                return $this->teams[$i];
            }
        }

        return null;
    }

    public function swapOrder($i, $j) {
        list($this->teamSymbols[$i], $this->teamSymbols[$j]) = [$this->teamSymbols[$j], $this->teamSymbols[$i]];
        list($this->teams[$i], $this->teams[$j]) = [$this->teams[$j], $this->teams[$i]];
    }

    public function reorderBySymbols(array $symbols) {
        $this->teamSymbols = $symbols;
        $newTeams = [];

        foreach($symbols as $symbol) {
            $newTeams[] = $this->getTeamBySymbol($symbol);
        }

        $this->teams[] = $newTeams;
    }
}