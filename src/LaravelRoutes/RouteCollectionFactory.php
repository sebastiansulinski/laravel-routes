<?php namespace SSD\LaravelRoutes;

use SSD\LaravelRoutes\Exceptions\InvalidClassName;
use SSD\LaravelRoutes\Exceptions\MissingNamespace;

abstract class RouteCollectionFactory
{
    /**
     * Call class and routes() method.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \SSD\LaravelRoutes\Exceptions\InvalidClassName
     */
    public static function __callStatic($name, $arguments)
    {

        $className = static::getName($name);

        if ( ! class_exists($className)) {

            throw new InvalidClassName;

        }

        return call_user_func_array([ new $className, 'routes' ], $arguments);

    }

    /**
     * Get class name with the namespace.
     *
     * @param $name
     * @return string
     */
    protected static function getName($name)
    {

        return static::getNameSpace() . "\\" . ucfirst($name);

    }

    /**
     * Get namespace for the given sections.
     *
     * @return string
     * @throws \SSD\LaravelRoutes\Exceptions\MissingNamespace
     */
    protected static function getNameSpace()
    {
        throw new MissingNamespace;
    }

}