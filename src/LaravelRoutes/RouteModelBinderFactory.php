<?php namespace SSD\LaravelRoutes;

use Illuminate\Routing\Router;

class RouteModelBinderFactory
{

    /**
     * Bind model to the route.
     *
     * @param RouteModelBinderContract $binder
     * @param Router $router
     * @return mixed
     */
    public static function bind(RouteModelBinderContract $binder, Router $router)
    {

        return $binder->bind($router);

    }

}