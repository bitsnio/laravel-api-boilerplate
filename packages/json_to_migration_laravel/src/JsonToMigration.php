<?php

namespace Bitsnio\JsonToLaravelMigrations;

use \Illuminate\Support\Collection;

class JsonToMigration extends Parameters {
    /**
     * Array schema of the JSON file
     * 
     * @var array
     */
    public $schema;

    /**
     * Schema migration methods
     */
    protected $methods;
    
    public function __construct($jsonPath, $destinationPath) {
        $this->load($jsonPath);
        $this->parse();
        $this->create($destinationPath);
    }
    
    private function parse() {
        $schemaParser = new SchemaParser($this->schema);
        $this->methods = $schemaParser->parse();
    }

    private function load(String $jsonPath) {
        $jsonParser = new JsonParser($jsonPath);
        $this->schema = $jsonParser->parse();
    }

    private function create($destinationPath) {
        $migrationCreator = new MigrationCreator($this->methods);
        $migrationCreator->create($destinationPath);
    }
}