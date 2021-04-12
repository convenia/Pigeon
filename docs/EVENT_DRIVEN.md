# Event Driven
"Event driven" is a well known microservice concept and Pigeon has some features that makes event driven a peace of cake.

## Dispatching Events

Very similar to `publish` method, Pigeon has an `dispatch` method.

```PHP
Pigeon::dispatch('payment.purchase.done', [
    'uuid' => $user->uuid,
    'value' => 1000
    'status' => 'Done'
], [
    'source' => 'payment.service',
    'dispatched_at' => '2019-04-11 12:00:01'
]);
```

Now we are emitting the `payment.purchase.done` event, by convention we use dot notation for event names and the first word is the service that is dispatching the event(the payment service).

1. The first parameter is the event name
2. The second parameter is the body message, this must be the main content.
3. The third param are the headers, it lives in `application_headers` property from message.

By the book, when you use publish method, you know the consumer, you send directally to the consumer(only one consumer), dispatch method is different, you don't know the consumer and actually it can have more than one consumer, it's a event/subiscriber architecture.

### How it works behind the scenes?

All events are send to the `events` exchange with the event name as its routing key, this way all listeners binded to `events` exchange can chose the event by the routing key.

If you are not acquainted with routing, see [rabbitmq docs](https://www.rabbitmq.com/tutorials/tutorial-four-php.html).

## Listening For Events

The previous section have introduced the `dispatch` method, but this method will send the event to an exchange without a binded queue, the emitter is talking but no one is listening... our shipping service should listen for orders from payment service, lets do this.

```PHP
Pigeon::events('payment.purchase.done')
    ->callback(function ($event, ResolverContract $resolver) {
        //emplement shipping tricks
        $resolver->ack();
    })->consume(0, true);
```

Just put this code in an artisan command and we are ready to go...

The method `events` will listen for events named with his param, the param should be identical to the event name used on dispatch method.

The callback method defines a handler to the event, the first param is the message body sent by `dispatch` method, the second param is the resolver.

!> Its very important to call `$resolver->ack();` when all goes well, if you dont, the message will never get out of the queue and the listener will process the message for ever

### How it works behind the scenes?

The method events will declare a queue with the event name sufixed with the value of environment variable `PIGEON_CONSUMER_TAG`, in this case our queue will be called `payment.purchase.done.shipping`, as you can see looking at the queue name you can realise who are the emitter an the listenner, it is very welcome when you have many listener listening for the same event.

The `event` method will bind the queue to the `events` exchange too, the param of event method will be the routing key("payment.purchase.done"), now the communication is up and running!!

### When the handling goes wrong

If any uncaptured exception rises on our shipping service a fallback will be called, this callback will write an error log containing the exception, stacktrace and message body, after that it will act based on `pigeon.consumer.on_failure` config value:

1. if value is `ack` the message will be acknowledged and gets out of the queue, the listener is kept alive, you should lost the message, it is nice to not important tasks.

2. if the value is `reject` the message will be rejected and returns to the queue, the listener will be kept alive, now ne message can be reprocessed soon as possible.

3. if the value is `throw` nothing happens with the message but the listener dies, this is nice to development environment because you can see the exception.

!> Its very recommended to run the listeners on top of supervisor, supervisor can revive the listener and scale them if necessary.

This is the Pieon default fallback but you can override this `fallback` by calling the `fallback` method after the `callback` method:

```PHP
Pigeon::events('payment.purchase.done')
    ->callback(...)
    ->fallback(function (Throwable $e, $message, $resolver) {
        //Do your fallback tricks
    })->consume(0, true);
```

This is important if you wish to send the exception to [sentry](https://sentry.io) for example. 

Keep in mind by overriding the default fallback you must reject or ack the message by yourself, you do have the resolver as a fallback parameter to realise this task.

The fallback receives the exception instance and the message as a parameter too then you can do smart things with it.

## Event Source

Event sourcing with Pigeon is too easy, all events sent to events exchange can be send to a single queue that is binded to `events` exchange with the `#` as routing key:

```PHP
Pigeon::events('#')->callback(function ($event, ResolverContract $resolver) {
    $headers = $resolver->headers('application_headers')->getNativeData();
    $eventDate = Carbon::parse(Arr::get($headers, 'date'));
    $localEvent = new Event();
    $localEvent->request_id = $resolver->headers('correlation_id');
    $localEvent->emitter = Arr::get($headers, 'emitter');
    $localEvent->source = Arr::get($headers, 'source');
    $localEvent->category = Arr::get($headers, 'category');
    $localEvent->author = Arr::get($headers, 'user_name') . " - " . Arr::get($headers, 'user_uuid');
    $localEvent->payload = $event;
    $localEvent->date_readable = $eventDate->toIso8601String();
    $localEvent->date_timestamp = $eventDate->timestamp;
    $localEvent->date_micro = $eventDate->micro;

    $localEvent->save();
    $resolver->ack();
})->consume(0);
```

This is a real example of a event store listener, keep in mind that this listener will be upon a high stress, you does need to keep this listener properly scaled.

## Dead Letter Management

The things can goes wrong, if the listener is broken and you reject the messagem, this message wil be reprocessed endlessly, it is a well known message broker problem but you can define a dead letter exchange to help with this problem.

The method `events` will see the configs `pigeon.dead.exchange` and `pigeon.dead.routing_key` to configure a dead letter exchange when the queue is declared, if you get a listener properly running and a queue binded to this exchange then this listener will receive all broken messages and you can store this for later handling, actually the messages are sent to dead letter exchange when the message is rejected and the dead letter exchange is declared as a queue property.

If you don't know what is a dead letter exchange [see the documentation](https://www.rabbitmq.com/dlx.html)