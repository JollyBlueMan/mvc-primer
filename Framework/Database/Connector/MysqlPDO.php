<?php

namespace Framework\Database\Connector;

use Framework\Database;
use Framework\Database\Exception;
use PDO;
use PDOException;
use PDOStatement;

class MysqlPDO extends Database\Connector
{
    /**
     * @var PDO $_service connected pdo service
     */
    protected  $_service;

    /**
     * @var string $_host mysql host ip
     * @readwrite
     */
    protected $_host;

    /**
     * @var string $_username mysql username
     * @readwrite
     */
    protected $_username;

    /**
     * @var string $_password mysql user's password
     * @readwrite
     */
    protected $_password;

    /**
     * @var string $_schema mysql database name
     * @readwrite
     */
    protected $_schema;

    /**
     * @var int $_port mysql port
     * @readwrite
     */
    protected $_port = 3306;

    /**
     * @var string $_charset character set to use
     * @readwrite
     */
    protected $_charset = "utf8mb4";

    /**
     * @var string $_dsn PDO connection string
     * @readwrite
     */
    protected $_dsn;

    /**
     * @var string $_engine mysql engine to use
     * @readwrite
     */
    protected $_engine = "InnoDB";

    /**
     * @var array $_options PDO settings
     * @readwrite
     */
    protected $_options = [];

    /**
     * @var bool $_isConnected status of current mysql connection
     * @readwrite
     */
    protected $_isConnected = false;

    protected function _isValidService()
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof \PDO;

        if ($this->_isConnected && $isInstance && !$isEmpty)
        {
            return true;
        }

        return false;
    }

    public function connect()
    {
        if (!$this->_isValidService())
        {
            $this->_dsn = "mysql:host={$this->_host};dbname={$this->_schema};port={$this->_port};charset={$this->_charset}";
            $this->_options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];
            try
            {
                $this->_service = new \PDO(
                    $this->_dsn, $this->_username, $this->_password, $this->_options
                );
            }
            catch (\PDOException $e)
            {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }

            $this->_isConnected = true;
        }

        return $this;
    }

    public function disconnect()
    {
        if ($this->_isValidService())
        {
            $this->_isConnected = false;
            $this->_service = null;
        }

        return $this;
    }

    public function query()
    {
        return new Database\Query\MysqlPDO([
            "connector" => $this
        ]);
    }

    /**
     * @param $sql
     * @return false|PDOStatement
     * @throws Exception\Service
     */
    public function execute($sql)
    {
        if (!$this->_isValidService())
        {
            throw new Exception\Service("Not connected to a valid service");
        }

        /*$stmt = $this->_service->prepare($sql);
        if (!$stmt)
        {
            return $stmt;
        }
        return $stmt->execute();*/
        return $this->_service->query($sql);
    }


    public function getLastInsertId()
    {
        if (!$this->_isValidService())
        {
            throw new Exception\Service("Not connected to a valid service");
        }

        return $this->_service->lastInsertId();
    }

    /**
     * @param $result PDOStatement
     * @return mixed
     * @throws Exception\Service
     */
    public function getAffectedRows($result)
    {
        if (!$this->_isValidService())
        {
            throw new Exception\Service("Not connected to a valid service");
        }

        return $result->rowCount();
    }

    /**
     * @param $result PDOStatement
     * @return mixed
     * @throws Exception\Service
     */
    public function getLastErrorMessage($result)
    {
        if (!$this->_isValidService())
        {
            throw new Exception\Service("Not connected to a valid service");
        }

        return $result->errorInfo();
    }

    /**
     * @param $result PDOStatement
     * @return mixed
     * @throws Exception\Service
     */
    public function getLastErrorCode($result)
    {
        if (!$this->_isValidService())
        {
            throw new Exception\Service("Not connected to a valid service");
        }

        return $result->errorCode();
    }

    public function sync($model)
    {
        $lines    = [];
        $indices  = [];
        $columns  = $model->columns;
        $template = "CREATE TABLE `%s` (\n%s, \n%s\n) ENGINE=%s DEFAULT CHARSET=%s;";

        foreach ($columns as $column)
        {
            $raw    = $column["raw"];
            $name   = $column["name"];
            $type   = $column["type"];
            $length = $column["length"];

            if ($column["primary"])
            {
                $indices[] = "PRIMARY KEY (`{$name}`)";
            }
            if ($column["index"])
            {
                $indices[] = "KEY `{$name}` (`{$name}`)";
            }

            switch ($type)
            {
                case "autonumber":
                    $lines[] = "`{$name}` int(11) NOT NULL AUTO_INCREMENT";
                    break;
                case "text":
                    if ($length !== null && $length <= 255)
                    {
                        $lines[] = "`{$name} varchar({$length}) DEFAULT NULL";
                    }
                    else
                    {
                        $lines[] = "`{$name}` text";
                    }
                    break;
                case "integer":
                    $lines[] = "`{$name}` int(11) DEFAULT NULL";
                    break;
                case "decimal":
                    $lines[] = "`{$name}` float DEFAULT NULL";
                    break;
                case "boolean":
                    $lines[] = "`{$name}` tinyint(4) DEFAULT NULL";
                    break;
                case "datetime":
                    $lines[] = "`{$name}` datetime DEFAULT NULL";
            }

            $table = $model->table;
            $sql = sprintf(
                $template,
                $table,
                join(",\n", $lines),
                join(",\n", $indices),
                $this->_engine,
                $this->_charset
            );

            $result = $this->execute("DROP TABLE IF EXISTS {$table};");
            if ($this->getLastErrorCode($result))
            {
                $error = $this->getLastErrorMessage($result);

            }
        }
    }
}