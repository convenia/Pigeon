# ADVANCED USAGE
## Table of Contents {docsify-ignore}
 1. [Introducing](#introducing)
 1. [Headers](#headers)
 
## Introducing
 Let's see some advanced uses of Pigeon
 
## Headers
 You can easily add `application_headers` when publishing a message with the `header` method.
 
 ```php
use Pigeon;

Pigeon::routing($routing)
    ->header($key, $value);    
```  

?> You can also pass **associative array** as a value for the header, it will be converted to dot notation

You can also capture all message headers with the resolver in your callback with `headers` method
 ```php
 use Pigeon;
 
$callback = function ($data, $resolver) {
    $resolver->headers();
};
 Pigeon::queue($queue)
    ->callback($callback);
 ```  
 
?> If you want a specific header you can pass it key to headers and it'll return only the header you asked for

!> In the opposite of publishing message, when consuming headers will not return only the **application_headers**
but all AMQPMessage internal headers

## Pigeon on queue jobs

AMPQ connections should reused in the same process, the connection roud trip needs 7 packages to be done, the message needs only one to be send, if you close connection on every publish you will be compromising performance and trafic.

If you are using `php artisan queue:work` you have multiple tasks performing as a single process, if the process takes too long to "talk" with rabbit instance the connection will be closed, causing a `AMQPConnectionClosedException`

In this case you should call `Pigeon::getConnection()->close();` at the end of all AMQP related jobs.

```PHP
Pigeon::exchange('queue')->publish([
    'work'
]);

Pigeon::getConnection()->close();
```
