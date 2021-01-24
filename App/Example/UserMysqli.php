<?php

namespace App\Example;

use Framework\Database;
use Framework\Core\Exception;

class UserMysqli
{

    /**
     * @var Database\Connector\Mysql
     */
    protected $database;

    protected $data;

    public function __construct()
    {
        $connection = new Database([
            "type" => "mysqli",
            "options" => [
                "host"     => "",
                "username" => "",
                "password" => "",
                "schema"   => "",
                "port"     => ""
            ]
        ]);

        $this->database = $connection->initialise();
    }

    public function getAll(){
        $this->database->query()->run("SELECT * FROM users;");

    }

}