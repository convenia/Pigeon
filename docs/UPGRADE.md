# Upgrade Guide
## From 1.x to 2.x{docsify-ignore}
 1. [Laravel 8](#laravel-8)
 2. [Old Laravel Versions](#old-laravel-versions)
 3. [Old PHP Versions](#old-php-versions)
 4. [Change emmit method](#change-emmit-method)
 5. [Removed methods](#removed-methods)
 
### Laravel 8
If you are considering use Pigeon Version 2.x you does need laravel 8.

### Old Laravel Versions
Pigeon 2.0 will not support Laravel version 5.6, if you are using 5.6 consider upgrade your application or use Pigeon 1.x version. Pigeon 2.x can talk with 1.x then if you have more than one services talking through Pigeon you can upgrade each one separately

### Old PHP Versions
Pigeon 2.0 will not support PHP versions lower than 7.3. If you are using version 7.2 you can upgrade your application or use Pigeon 1.x

### Change emmit method
Method name `emmit` has changed to `dispatch`, update all `Pigeon::emmit()` to `Pigeon::dispatch()`
 

 ### Removed methods
All RPC methods and test tools were removed. The RPC was just a wrapper for some Pigeon calls and you still can do RPC with this version but you will need to do this by yourself

The RPC methods `Pigeon::rpc` and `Convenia\Pigeon\Resolver::response` were removed
The RPC Test methods `Pigeon::assertRpc`, `Pigeon::rpcPushed` and `Pigeon::assertCallbackReturn` were removed
