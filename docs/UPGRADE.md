# Upgrade Guide
## From 1.x to 2.x{docsify-ignore}
 1. [Drop Old Laravel Versions](#old-laravel-versions)
 2. [Drop Old PHP Versions](#old-php-versions)
 3. [Change emmit method](#change-emmit-method)
 4. [Removed methods](#removed-methods)
 5. [Refactoring RPC](#refactoring-rpc)
 
### Old Laravel Versions
Pigeon 2.0 will not support Laravel version 5.6, if you are using 5.6 consider upgrade your application or use Pigeon 1.x version. Pigeon 2.x can talk with 1.x then if you have more than one services talking through Pigeon you can upgrade each one separately

### Old PHP Versions
Pigeon 2.x will not support PHP versions lower than 7.3. If you are using version 7.2 you can upgrade your application or use Pigeon 1.x

### Change emmit method
Method name `emmit` has changed to `dispatch`, update all `Pigeon::emmit()` to `Pigeon::dispatch()`
 
 ### Removed methods
All RPC methods and test tools were removed. The RPC was just a wrapper for some Pigeon calls and you still can do RPC with this version but you will need to do this by yourself

The RPC methods `Pigeon::rpc` and `Convenia\Pigeon\Resolver::response` were removed
The RPC Test methods `Pigeon::assertRpc`, `Pigeon::rpcPushed` and `Pigeon::assertCallbackReturn` were removed

### Refactoring RPC:

If you have any RPC written with the previous Pigeon version you can follow this refactoring example.

This is a real RabbitMQ RPC request written in Pigeon 1.x :

```PHP
$responseQueue = Pigeon::routing('employees.status')
    ->bind('core:employees.status')
    ->rpc([
        'company_uuid' => $event['uuid'],
        'status' => Employee::STATUS_ACTIVE,
    ]);

Pigeon::queue($responseQueue)->callback(function ($message) {
    //Do cool stuff
})->consume(0, false);
```

The same RPC request written in Pigeon 2.x :

```PHP
[$replyQueue,] = Pigeon::getChannel()->queue_declare();

Pigeon::routing('employees.status')
    ->bind('core:employees.status')
    ->publish([
        'company_uuid' => $event['uuid'],
        'status' => Employee::STATUS_ACTIVE,
    ], [
        'reply_to' => $replyQueue
    ]);

Pigeon::queue($replyQueue)->callback(function ($message) {
    //Do the same
})->consume(0, false);
```

You only need to upgrade the code that is receiving the request if you plan to upgrade Pigeon in this application too. If so you can apply the following:

This is the RPC receiver written in Pigeon 1.x :

```PHP
Pigeon::queue('core:employees.status')
    ->callback(function ($message, ResolverContract $resolver) {
        $coolResponse = "Scooby Dooo";
        $resolver->response($coolResponse);
    })->consume(0);
```

This is the same RPC receiver written in Pigeon 2.x :

```PHP
Pigeon::queue('core:employees.status')
    ->callback(function ($message, ResolverContract $resolver) {
        $coolResponse = [
            "Scooby" => "Dooo"
        ];

        if ($resolver->headers('reply_to')) {
            $queue = $resolver->headers('reply_to');
            $msg = new AMQPMessage(json_encode($coolResponse)), [
                'correlation_id' => $resolver->headers('correlation_id')
            ]);

            $resolver->message->delivery_info['channel']
                ->basic_publish($msg, '', $queue);
        }

        $resolver->ack();

    })->consume(0);
```


**IF YOU ARE DOING RPC USING PIGEON, PLEASE CONSIDER AN HTTP CALL INSTEAD**