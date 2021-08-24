# Upgrade Guide
## From 1.x to 2.x{docsify-ignore}
 1. [Laravel 8](#laravel-8)
 2. [Change emmit method](#change-emmit-method)
 3. [Removed methods](#removed-methods)
 
### Laravel 8
If you are considering use Pigeon Version 2.x you does need laravel 8.

### Change emmit method
Method name `emmit` has changed to `dispatch`, upade all `Pigeon::emmit()` to `Pigeon::dispatch()`
 

 ### Removed methods
All RPC methods and test tools were removed. The RPC was just a wrapper for some Pigeon calls and you still can do RPC with this version but you will need to do this by yourself

The RPC methods `Pigeon::rpc` and `Convenia\Pigeon\Resolver::response` were removed
The RPC Test methods `Pigeon::assertRpc`, `Pigeon::rpcPushed` and `Pigeon::assertCallbackReturn` were removed
