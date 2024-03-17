<?php

namespace Convenia\Pigeon\RabbitMQ\Message;

use Closure;
use Convenia\Pigeon\Contracts\Driver as DriverContract;
use Convenia\Pigeon\Contracts\Message\Processor as ProcessorContract;
use Convenia\Pigeon\MessageResolver;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class Processor implements ProcessorContract
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

        call_user_func($this->callback, $data, new MessageResolver($message));
    }

    private function callUserFallback(Throwable $t, $message)
    {
        $resolver = new MessageResolver($message);
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
