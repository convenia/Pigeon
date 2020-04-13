# Testing
## Table of Contents {docsify-ignore}
 1. [Getting Started](#getting-started)
 2. [Asserts](#asserts)
 
## Getting Started
Pigeon uses Laravel's coding style, so it can take advantages of the framework's features.

You can fake a connection to the broker using `Pigeon::fake()` to speed up your tests.
 
## Asserting
 Pigeon has methods to test your code, assert certain behaviors and check if things are working.
 
### Publishing
 You can easily check if a message was correctly published using the `assertPublished` method;
 
 Let's assume you have a controller that publishes a message:
 ```php
Pigeon::routing('employe.deleted')
    ->publish([
        'employee_id' => 2
    ]);
```

The code above can be tested using the `assertPublished` method, which will check if the message was published in the specified route:
```php
Pigeon::fake();

// Call your controller

Pigeon::assertPublished(
    'employee.deleted',
    [
        'employee_id' => 2
    ]
);
```

### Consuming
Just like asserting a published message, you can easily assert that a consumer has been started in the correct queue using `assertConsuming`.

The `timeout` and `multiple` parameters are optional, if you don't pass this parameters then timeout and multiplicity won't be checked. 
```php
Pigeon::fake();

// Start the consumer

Pigeon::assertConsuming('the.queue.name', $timeout = 5, $multiple = true);
```

You can also call the callback configured in the consumer using the `dispatchConsumer`
```php
Pigeon::fake();

// Start the consumer

Pigeon::dispatchConsumer(
    'the.queue.name',
    ['mocked' => 'message'] // mocked incoming message
);
``` 

### RPC
You can use `assertRpc` method to test RPC, it is basically the consumer and the publisher assertions together with hability to define the RPC response stub.

!> When testing RPCs the env exchange will be always assumed, you can not override the exchange for now.

This is an RPC test sample:

```php
<?php
//app/services/RpcService.php
namespace App\Service;

use Convenia\Pigeon\Facade\Pigeon;

class RpcService
{
    public function handle()
    {
        //rpc method will return a fake queue
        $queue = Pigeon::routing('my.routing.key')->rpc([
            'scooby'
        ]);

        //just a simple consumer
        Pigeon::queue($queue)->callback(function ($response) {
            dd($response);
            /**
             * array:1 [
             *     0 => "doo"
             * ]
             */
        })->consume(5, false);
    }
}
```

```php
//RpcTest.php

Pigeon::fake();

//call application code
$service = new App\Services\RpcService()
$service->handle();

//this method needs to be called after all
//this can blow your mind but here you will define the $response passed to callback
Pigeon::assertRpc('my.routing.key', ['scooby'], ['doo']);
```
The snippet above will assert the routing key and the data sent by rpc, and define a response stub that will be send to que RPC consumer.

You can assert timeout and multiplicity like assertConsuming assertion. normally multiplicity will be false

```php
Pigeon::assertRpc($routing, $expectedData, $expectedResponse, $timeout = 5, $multiplicity = false);
``` 