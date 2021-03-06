<?php
namespace Framework\Template;

use Framework\Base;
use Framework\StringMethods;
use Framework\Template\Exception;

class Implementation extends Base
{
    protected function _handler($node)
    {
        if (empty($node["delimiter"])) {
            return null;
        }

        if (!empty($node["tag"])) {
            return $this->_map[$node["delimiter"]]["tags"][$node["tag"]]["handler"];
        }

        return $this->_map[$node["delimiter"]]["handler"];
    }

    public function handle($node, $content)
    {
        try {
            $handler = $this->_handler($node);
            return call_user_func_array([$this, $handler], [$node, $content]);
        } catch (\Exception $e) {
            throw new Exception\Implementation();
        }
    }

    public function match($source)
    {
        $type      = null;
        $delimiter = null;

        foreach ($this->_map as $_delimiter => $_type) {
            if (!$delimiter || StringMethods::indexOf($source, $type["opener"]) == -1) {
                $delimiter = $_delimiter;
                $type      = $_type;
            }

            $indexOf = StringMethods::indexOf($source, $_type["opener"]);

            if ($indexOf > -1) {
                if (StringMethods::indexOf($source, $type["opener"]) > $indexOf) {
                    $delimiter = $_delimiter;
                    $type      = $_type;
                }
            }
        }

        if ($type == null) {
            return null;
        }

        return [
            "type"      => $type,
            "delimiter" => $delimiter
        ];
    }
}