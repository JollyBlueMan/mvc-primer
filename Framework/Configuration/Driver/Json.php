<?php
namespace Framework\Configuration\Driver;

use Framework\ArrayMethods;
use Framework\Configuration\Driver;
use Framework\Configuration\Exception;

class Json extends Driver
{

    public function parse($path)
    {
        if (empty($path)) {
            throw new Exception\Argument("\$path argument is not valid");
        }

        if (!isset($this->_parsed[$path])) {
            $config = [];

            $string = file_get_contents("{$path}.json");

            $pairs = json_decode($string, true);

            if ($pairs == false) {
                throw new Exception\Syntax("Could not parse json configuration file.");
            }

            $this->_parsed[$path] = ArrayMethods::toObject($pairs);

        }

        return $this->_parsed[$path];
    }
}
