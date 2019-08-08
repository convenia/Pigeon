<?php

namespace Convenia\AMQP\Drivers;

use Exception;
use Convenia\AMQP\Exceptions\DeclareException;
use Convenia\amqp\Exceptions\RpcResponseException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class RabbitDriver.
 */
class RabbitDriver extends Driver
{
    /**
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    private $connection;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    /**
     * @var \Convenia\AMQP\Drivers\Resolver
     */
    private $resolver;

    /**
     * @var \PhpAmqpLib\Message\AMQPMessage
     */
    private $message;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $exchangeType;

    /**
     * RabbitDriver constructor.
     *
     * @param \Illuminate\Foundation\Application          $app
     * @param \PhpAmqpLib\Connection\AMQPStreamConnection $connection
     */
    public function __construct(Application $app, AMQPStreamConnection $connection)
    {
        parent::__construct($app);
        $this->connection = $connection;
    }

    /**
     * Consume a queue.
     *
     * @param string $queue
     * @param int    $timeout
     * @param bool   $multiple
     *
     * @throws \ErrorException
     * @throws \Throwable
     */
    public function consume(string $queue, int $timeout = 1, bool $multiple = true): void
    {
        // declare the queue if it does not exists
        $this->declareQueue($queue);

        // start consuming
        $this->channel->basic_consume(
                $queue,
                $this->applicationTag(),
                false,
                $this->app['config']['amqp.consumer.automatic_ack'],
                false,
                false,
                [$this, 'receive']
            );

        // loop as long as the channel has callbacks registered
        do {
            $this->channel->wait(null, null, $timeout);
        } while ($this->channel->callbacks && $multiple);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function publish(string $routingKey, array $message, string $queue = null, array $properties = []): void
    {
        $this->declareExchange($this->exchange, $this->exchangeType);
        $this->declareQueue($queue);
        $this->bindQueue($this->exchange, $queue, $routingKey);

        $message = new AMQPMessage(json_encode($message), $this->getProps($properties));

        $this->channel->basic_publish($message, $this->exchange, $routingKey);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function asyncRpc(string $routingKey, array $message, string $queue = null, array $properties = []): string
    {
        $responseQueue = $this->declareQueue(null);

        try {
            $this->publish($routingKey, $message, $queue, [
                'reply_to' => $responseQueue,
            ]);

            return $responseQueue;
        } catch (\Throwable $e) {
            $this->channel->queue_delete($responseQueue);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rpc(string $routingKey, array $message, string $queue = null, array $properties = [], int $timeout = 10): array
    {
        $responseQueue = $this->asyncRpc($routingKey, $message, $queue, $properties);
        $response = [];
        $callback = static function ($rpcResponse) use (&$response) {
            $response = $rpcResponse;
        };
        try {
            $this->consume($responseQueue, $timeout, false);
            $this->resolver->ack();
        } catch (\Throwable $e) {
            $this->resolver->reject(false);
            throw $e;
        }

        return $response;
    }

    /**
     * Declare a queue.
     *
     * @param string $queue
     * @param array  $props
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function declareQueue(string $queue, array $props = []): string
    {
        if (str_contains($queue, 'amq.')) {
            return null;
        }

        [$queue, ] = $this->channel->queue_declare($queue, ...$props);

        return $queue;
    }

    /**
     * Declare a exchange.
     *
     * @param string $exchange
     * @param string $type
     * @param array  $props
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function declareExchange(string $exchange, string $type = 'direct', array $props = []): bool
    {
        throw_if(
            str_contains($exchange, 'amq.'),
            new DeclareException("The queue name cannot contain 'amq'")
        );

        [$exchange, ] = $this->channel->exchange_declare($exchange, $type, ...$props);

        return $exchange;
    }

    /**
     * Bind a queue to exchange using or not a routing key.
     *
     * @param string      $exchange
     * @param string      $queue
     * @param string|null $routingKey
     *
     * @throws \Throwable
     */
    public function bindQueue(string $exchange, string $queue, string $routingKey = null)
    {
        throw_if(
            empty($queue)
            || empty($exchange),
            new DeclareException('The queue or exchange cannot be empty.')
        );
        $this->channel->queue_bind($queue, $exchange, $routingKey);
    }

    /**
     * @return \PhpAmqpLib\Message\AMQPMessage
     */
    public function getMessage(): AMQPMessage
    {
        return $this->message;
    }

    /**
     * Publish a message directly into a queue.
     *
     * @param                                 $queue
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function direct($queue, AMQPMessage $message): void
    {
        $this->channel->basic_publish($message, '', $queue);
    }

    /**
     * Setup connection for use.
     */
    protected function setup()
    {
        $this->channel = $this->connection->channel(1);

        // fix the qos
        $this->channel->basic_qos(null, 1, null);

        // clear at shutdown
        $this->connection->set_close_on_destruct(true);

        // create driver resolver
        $this->resolver = new Resolver($this->app, $this);

        // get exchange
        $this->exchange = $this->app['config']['amqp.exchange'];
        $this->exchangeType = $this->app['config']['amqp.exchange_type'];
    }

    /**
     * @param array $properties
     *
     * @return array
     */
    protected function getProps(array $properties): array
    {
        if (!$this->getMessage()) {
            return $this->app['config']['amqp.messages.property'] ?: [];
        }

        $message = $this->getMessage();

        $properties = array_merge($properties, $this->app['config']['amqp.messages.property'] ?: []);

        if ($message->has('correlation_id')) {
            $properties['correlation_id'] = $message->get('correlation_id');
        } else {
            $properties['correlation_id'] = $this->correlationId();
        }

        return $properties;
    }

    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     *
     * @throws \Exception
     */
    protected function receive(AMQPMessage $message): void
    {
        $this->message = $message;

        // get message data
        $data = json_decode($message->body, true);

        try {
            // check if the message is a exception from sender side
            if (is_array($data) && array_has($data, 'X-error')) {
                throw new RpcResponseException($data['X-error'], 500, null, $data['X-body']);
            }

            // get response from user callback
            $response = call_user_func($this->callback, $data, $this->resolver) ?: [];

            // return content in case of RPC
            $this->returnResponse($message, $response);
        } catch (Exception $e) {
            if ($this->fallback) {
                call_user_func($this->fallback, $e, $this->resolver);
            } else {
                $this->resolver->reject();

                $response = [
                    'X-error' => $e->getMessage(),
                    'X-previous' => $e->getPrevious(),
                    'X-body' => $data,
                ];

                Log::error(
                    $e->getMessage(),
                    [
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                        'trace' => $e->getTraceAsString(),
                        'message' => $data,
                        'properties' => $message->get_properties(),
                    ]
                );

                // return error in case of RPC
                $this->returnResponse($message, $response);

                throw $e;
            }
        }
    }

    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $request
     * @param array                           $response
     *
     * @return int
     */
    protected function returnResponse(AMQPMessage $request, array $response = []): void
    {
        if ($request->has('reply_to')) {
            $message = new AMQPMessage(
                json_encode($response),
                ['correlation_id' => $request->get('correlation_id')]
            );

            $this->direct($request->get('reply_to'), $message);
        }
    }
}
