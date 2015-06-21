<?php namespace SSD\LaravelRoutes;

use Illuminate\Routing\Router;

interface RouteModelBinderContract
{

    /**
     * Bind model to the route.
     *
     * @param Router $router
     * @return mixed
     */
    public function bind(Router $router);

}