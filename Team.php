<?php

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
}