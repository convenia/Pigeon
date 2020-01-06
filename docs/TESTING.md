# Testing
## Table of Contents {docsify-ignore}
 1. [Getting Started](#getting-started)
 2. [Asserts](#asserts)
 
## Getting Started
Pigeon uses the Laravel code style, so it can take advantages of the framework features.

The basic of testing with Pigeon is to fake it using `Pigeon::fake()` so it will not create a real connection to broker
and will speed up the test.
 
## Asserts
 We added some methods to test your code and assert certain behaviours and check if things worked.
 
### Publish
 You can easily check if a message was correct published using the `assertPublished` method;
 
 Lets assume you have a controller that publish some message:
 ```php
Pigeon::routing('employe.deleted')
    ->publish([
        'employee_id' => 2
    ]);
```

The above code can be tested using the `assertPublished`, which wi'll check if the message was published in the specified:
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

### Consume
Like asserting a message was published, you can easily assert a consumer has started in the correct queue using the `assertConsuming`.

The `timeout` and `multiple` params are optional, if you dont pass this params then timeout and multiplicity wont be checked. 
```php
Pigeon::fake();

// Call the consumer start

Pigeon::assertConsuming('the.queue.name', $timeout = 5, $multiple = true);
```

You can also call the callback configured in the consumer using the `dispatchConsumer`
```php
Pigeon::fake();

// Call the consumer start

Pigeon::dispatchConsumer(
    'the.queue.name',
    ['mocked' => 'message'] // mocked incoming message
);
``` 
