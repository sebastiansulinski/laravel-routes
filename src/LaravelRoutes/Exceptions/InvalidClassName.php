<?php namespace SSD\LaravelRoutes\Exceptions;

use Exception;

class InvalidClassName extends Exception
{

    protected $message = "Invalid class name by static method call";

}