<?php

namespace Framework;

use Framework\Base;
use Framework\Cache\Exception;

class Cache extends Base
{
    /**
     * @var string $_type Caching engine to use
     * @readwrite
     */
    protected $_type;

    /**
     * @var array $_options Settings for caching engine
     * @readwrite
     */
    protected $_options;

    protected function _getExceptionForImplementation($method)
    {
        return new Exception\Implementation("{$method} method not implemented");
    }

    public function initialise()
    {
        $type = $this->type;

        if (!$type)
        {
            /** @var Configuration $configuration */
            $configuration = Registry::get("configuration");

            if ($configuration)
            {
                $configuration = $configuration->initialise();
                $parsed        = $configuration->parse("Configuration/_cache");

                if (!empty($parsed->cache->default) && !empty($parsed->cache->default->type))
                {
                    $this->type    = $parsed->cache->default->type;
                    unset($parsed->cache->default->type);
                    $this->options = (array) $parsed->cache->default;
                }
            }
        }

        if (!$type)
        {
            throw new Exception\Argument("Invalid type");
        }

        switch ($type)
        {
            case "memcached":
                return new Cache\Driver\Memcached($this->options);
                break;
            default:
                throw new Exception\Argument("Invalid type");
                break;
        }
    }
}