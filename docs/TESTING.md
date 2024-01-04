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

There is a way to only verify the expected quatity of messages sent.

If you want to assert the exact number of messages, use `assertDispatchCount`.
```php
<?php

use Convenia\Pigeon\Facade\Pigeon;

Pigeon::fake();

Pigeon::dispatch('my-queue', [
    'some-data' => 123,
]);

Pigeon::dispatch('another-great-queue', [
    'some-data' => 123,
]);

Pigeon::assertDispatchCount(2); // Returns true
```

If you want to only check if nothing was sent, use `assertNothingDispatched`.
```php
<?php

use Convenia\Pigeon\Facade\Pigeon;

Pigeon::fake();

Pigeon::dispatch('my-queue', [
    'some-data' => 123,
]);

Pigeon::assertNothingDispatched(); // Returns false
```
