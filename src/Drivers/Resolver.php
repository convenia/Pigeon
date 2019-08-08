<?php

namespace Convenia\AMQP\Drivers;

use Convenia\AMQP\Contracts\Resolver as ResolverContract;
use Illuminate\Foundation\Application;

/**
 * Class Resolver.
 */
class Resolver implements ResolverContract
{
    /**
     * @var
     */
    protected $app;

    /**
     * @var
     */
    protected $driver;

    /**
     * Resolver constructor.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @param \Convenia\AMQP\Drivers\RabbitDriver $driver
     */
    public function __construct(Application $app, RabbitDriver $driver)
    {
        $this->app = $app;
        $this->driver = $driver;
    }

    /**
     * @param bool $requeue
     */
    public function reject(bool $requeue = true): void
    {
        $info = $this->getDeliveryInfo();
        $info['channel']->basic_reject($info['delivery_tag'], $requeue);
    }

    public function ack(): void
    {
        $info = $this->getDeliveryInfo();
        $info['channel']->basic_ack($info['delivery_tag']);
    }

    /**
     * @return array
     */
    private function getDeliveryInfo(): array
    {
        /* @var $channel \PhpAmqpLib\Channel\AMQPChannel **/
        return $this->driver->getMessage()->delivery_info;
    }
}
