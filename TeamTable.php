<?php
require_once('Team.php');
require_once('Group.php');
require_once('TeamComparator.php');

class TeamTable
{

    private $teamSymbols = [];
    private $teams = [];
    private Group $group;
    private $parentTable = null;

    public function __construct(Group $group, TeamTable $parentTable = null)
    {
        $this->group = $group;
        $this->parentTable = $parentTable;
    }

    public function sumResults($restrictedTeamSymbols = []): void
    {
        foreach ($this->group->getMatches() as $match) {
            $teamA = $match->getTeamA();
            $teamB = $match->getTeamB();

            $symbolA = $teamA->symbol;
            $symbolB = $teamB->symbol;

            if (!empty($restrictedTeamSymbols)) {
                foreach ($restrictedTeamSymbols as $restrictedSymbol) {
                    if ($restrictedSymbol == $symbolA || $restrictedSymbol == $symbolB) {
                        echo "Team: {$restrictedSymbol} is restricted! | Match: {$symbolA} vs {$symbolB} \n";
                    }
                }
                continue;
            }

            if (!in_array($symbolA, $this->teamSymbols)) {
                $this->teamSymbols[] = $symbolA;
                $this->teams[] = new Team($symbolA);
            }
            if (!in_array($symbolB, $this->teamSymbols)) {
                $this->teamSymbols[] = $symbolB;
                $this->teams[] = new Team($symbolB);
            }

            $this->getTeamBySymbol($symbolA)->addMatchResult($teamA, $teamB);
            $this->getTeamBySymbol($symbolB)->addMatchResult($teamB, $teamA);
        }
    }

    public function calculateOrder(): void
    {
        $smallTables = [];

        for ($i = 0; $i < count($this->teams) - 1; ++$i) {
            for ($j = $i + 1; $j < count($this->teams); ++$j) {
                if ($i == $j) {
                    continue;
                }

                $team1 = $this->teams[$i];
                $team2 = $this->teams[$j];
                $comparator = new TeamComparator($this);

                $compareResult = $comparator->compare($team1, $team2);
                if ($compareResult > 0) {
                    $this->swapOrder($i, $j);
                } elseif ($compareResult == 0) {
                    $hash = md5($team2->points . $team2->goalsBalance . $team2->scoredGoals);
                    if (!array_key_exists($hash, $smallTables)) {
                        $smallTables[$hash] = new TeamTable($this->group, $this);
                    }

                    $smallTables[$hash]->addTeam($team1);
                    $smallTables[$hash]->addTeam($team2);
                }
            }
        }

        if (!empty($smallTables)) {
            foreach ($smallTables as $hash => $smallTable) {
                $smallTable->sumResults($smallTable->getTeamSymbols());
                $smallTable->calculateOrder();

                $this->reorderPartByTable($smallTable);
            }
        }
    }

    public function addTeam(Team $team): void
    {
        $this->teamSymbols[] = $team->symbol;
        $this->teams[] = $team;
    }

    public function getTeamSymbols(): array
    {
        return $this->teamSymbols;
    }

    public function getTeams(): array
    {
        return $this->teams;
    }

    public function print(): void
    {
        $i = 1;
        foreach ($this->teams as $team) {
            $fairPlayResult = $this->group->getTeamFairPlayResultBySymbol($team->symbol);

            echo ($i++) . ". $team->symbol - $team->points pkt. | $team->scoredGoals-$team->concededGoals ($fairPlayResult) | fastest goal at {$team->fastestGoal} \n";
        }
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getParentTable(): TeamTable|null
    {
        return $this->parentTable;
    }

    private function getTeamBySymbol(string $symbol): Team|null
    {
        for ($i = 0; $i < count($this->teams); ++$i) {
            if ($this->teams[$i]->symbol == $symbol) {
                return $this->teams[$i];
            }
        }

        return null;
    }

    private function swapOrder($i, $j): void
    {
        list($this->teamSymbols[$i], $this->teamSymbols[$j]) = [$this->teamSymbols[$j], $this->teamSymbols[$i]];
        list($this->teams[$i], $this->teams[$j]) = [$this->teams[$j], $this->teams[$i]];
    }

    private function reorderPartByTable(TeamTable $table): void
    {
        $newTeamSymbols = [];
        foreach ($this->teamSymbols as $symbol) {
            if (in_array($symbol, $table->getTeamSymbols())) {
                $newTeamSymbols = array_merge($newTeamSymbols, $table->getTeamSymbols());
            } else {
                $newTeamSymbols[] = $symbol;
            }
        }

        $this->reorderBySymbols(array_values(array_unique($newTeamSymbols)));
    }

    private function reorderBySymbols(array $symbols): void
    {
        $this->teamSymbols = $symbols;
        $newTeams = [];

        foreach ($symbols as $symbol) {
            $newTeams[] = $this->getTeamBySymbol($symbol);
        }

        $this->teams = $newTeams;
    }
}
