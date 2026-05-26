<?php

namespace App\Support;

use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    protected static $instance = null;
    protected array $services = [];

    public function get(string $id) : mixed
    {
        $service = $this->resolve($id);
        return $this->getInstance($service);
    }

    public function has(string $id) : bool
    {
        return array_key_exists($id, $this->services);
    }

    private function resolve(string $id) : ReflectionClass
    {
        if($this->has($id)){
            $service = $this->services[$id];
            if(is_callable($service)){
                return $service();
            }
            return $service;
        }

        $this->services[$id] = new ReflectionClass($id);
        return $this->services[$id];
    }

    private function getInstance(ReflectionClass $service)
    {
        $constructor = $service->getConstructor();
        if(is_null($constructor) || $constructor->getNumberOfParameters() == 0){
            return $service->newInstance();
        }
        $params = [];
        foreach($constructor->getParameters() as $parameter){
            if($paramType = $parameter->getType()){
                $params[] = $this->get($paramType->getName());
            }
        }

        return $service->newInstanceArgs($params);
    }

    public function register(string $id, string $target = null) : void
    {
        $target = $target ?? $id;
        $this->services[$id] = new ReflectionClass($target);
    }

    public static function getContainer() : Container
    {
        if(is_null(static::$instance)){
            static::$instance = new static;
        }
        return static::$instance;
    }
}