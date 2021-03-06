<?php
namespace Framework;

use stdClass;

class ArrayMethods
{
    /**
     * ArrayMethods constructor.
     */
    private function __construct()
    {
        // do nothing
    }

    /**
     *
     */
    private function __clone()
    {
        // do nothing
    }

    /**
     * @param $array
     * @return array
     */
    public static function clean($array){
        return array_filter($array, function ($item) {
            return !empty($item);
        });
    }

    /**
     * @param $array
     * @return array|string[]
     */
    public static function trim($array){
        return array_map(
            function ($item) {
                return trim($item);
            },
            $array
        );
    }

    public static function toObject($array)
    {
        $result = new stdClass();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result->{$key} = self::toObject($value);
            } else {
                $result->{$key} = $value;
            }
        }

        return $result;
    }

    public static function flatten($array, $return = [])
    {
        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $return = self::flatten($value, $return);
            } else {
                $return[]= $value;
            }
        }

        return $return;
    }

    public static function first($array)
    {
        if (sizeof($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[0]];
    }

    public static function last($array)
    {
        if (sizeof($array) == 0) {
            return null;
        }

        $keys = array_keys($array);
        return $array[$keys[sizeof($keys) - 1]];
    }
}
