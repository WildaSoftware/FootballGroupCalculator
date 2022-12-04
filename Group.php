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

        $this->matches = array_map(function($match) { return new MatchDTO($match); }, json_decode($fileContent, true));
        $this->teamFairPlayResults = [];
    }

    public function prepareTeams() {
        $table = new TeamTable($this);
        $table->sumResults();

        foreach($table->getTeams() as $team) {
            $this->teamFairPlayResults[$team->symbol] = $team->fairPlay;
        }
        
        echo print_r($this->teamFairPlayResults, true);

        return $table;
    }
    
    public function getMatches() {
        return $this->matches;
    }

    public function getTeamFairPlayResultBySymbol(string $symbol) {
        return $this->teamFairPlayResults[$symbol];
    }
}