<?php

namespace Convenia\Pigeon\MessageProcessor;

use Closure;
use Convenia\Pigeon\Resolver\Resolver;
use Convenia\Pigeon\Resolver\ResolverContract;
use Exception;
use Convenia\Pigeon\Drivers\DriverContract;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use ReflectionFunction;

class MessageProcessor implements MessageProcessorContract
{
    protected $callback;
    protected $fallback;
    protected $driver;

    public function __construct(DriverContract $driver, Closure $callback, Closure $fallback = null)
    {
        $this->driver = $driver;
        $this->callback = $callback;
        $this->fallback = $fallback;
    }

    public function process(AMQPMessage $message)
    {
        try {
            $this->callUserCallback($message);
        } catch (Exception $e) {
            $this->callUserFallback($e, $message);
        }
    }

    private function callUserCallback($message)
    {
        $data = json_decode($message->body, true);
        $args = (new ReflectionFunction($this->callback))->getParameters();

        if (count($args) > 1 && ResolverContract::class === $args[1]->getType()->getName()) {
            call_user_func($this->callback, $data, new Resolver($this->driver, $message));
        } else {
            call_user_func($this->callback, $data);
        }
    }

    private function callUserFallback(Exception $e, $message)
    {
        if (!$this->fallback) {
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tracing' => $e->getTraceAsString(),
                'previous' => $e->getPrevious(),
                'message' => json_decode($message->body, true),
            ]);
            throw $e;
        }
        call_user_func($this->fallback, $e, $message);
    }
}
