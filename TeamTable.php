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

    private function reorderBySymbols(array $symbols) {
        $this->teamSymbols = $symbols;
        $newTeams = [];

        foreach($symbols as $symbol) {
            $newTeams[] = $this->getTeamBySymbol($symbol);
        }

        $this->teams = $newTeams;
    }
}