<?php

namespace Convenia\Pigeon;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

/**
 * Laravel 8 changed app variable doing a BC.
 * this class holds a reference with the old container name
 * in order to ensure compatibility with old Laravel versions
 */
abstract class BridgeManager extends Manager
{

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->app = $container;
        parent::__construct($container);
    }
}
