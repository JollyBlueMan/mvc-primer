<?php

namespace Framework;

use Framework\Base;
use Framework\Database\Exception;

class Database extends Base
{
    /**
     * @var string $_type type of database driver to use
     * @readwrite
     */
    protected $_type;

    /**
     * @var
     * @readwrite
     */
    protected $_options = [];

    protected function _getExceptionForImplementation($method)
    {
        return new Exception\Implementation("{$method} method not implemented");
    }

    public function initialise()
    {
        if (!$this->type)
        {
            throw new Exception\Argument("Invalid type");
            //$this->type = "mysql_pdo";
        }

        if (!$this->options)
        {
            throw new Exception\Argument("No options provided");
            /*$this->options = [
                "host"     => "localhost",
                "username" => "prophpmvc",
                "password" => "prophpmvc",
                "schema"   => "prophpmvc",
                "port"     => "3306"
            ];*/
        }

        switch ($this->type)
        {
            case "mysqli":
                return new Database\Connector\Mysqli($this->options);
                break;
            case "mysql_pdo":
                return new Database\Connector\MysqlPDO($this->options);
                break;
            default:
                throw new Exception\Argument("Invalid type");
        }
    }

}