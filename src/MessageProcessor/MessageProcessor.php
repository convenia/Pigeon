<?php

namespace Convenia\Pigeon\MessageProcessor;

use Closure;
use Convenia\Pigeon\Drivers\DriverContract;
use Convenia\Pigeon\Resolver\Resolver;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

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
        } catch (Throwable $t) {
            $this->callUserFallback($t, $message);
        }
    }

    private function callUserCallback($message)
    {
        $data = json_decode($message->body, true);

        call_user_func($this->callback, $data, new Resolver($message));
    }

    private function callUserFallback(Throwable $t, $message)
    {
        $resolver = new Resolver($message);
        if (! $this->fallback) {
            return $this->defaultFallback($t, $message, $resolver);
        }
        call_user_func($this->fallback, $t, $message, $resolver);
    }

    private function defaultFallback(Throwable $t, $message, $resolver)
    {
        Log::error($t->getMessage(), [
            'file'     => $t->getFile(),
            'line'     => $t->getLine(),
            'tracing'  => $t->getTraceAsString(),
            'previous' => $t->getPrevious(),
            'message'  => json_decode($message->body, true),
        ]);

        switch (config('pigeon.consumer.on_failure')) {
            case 'ack':
                $resolver->ack();
                break;
            case 'reject':
                $resolver->reject(false);
                break;
            default:
                throw $t;
        }
    }
}
