# Upgrade Guide
## From 1.x to 2.x{docsify-ignore}
 1. [Laravel 8](#laravel-8)
 2. [Change emmit method](#change-emmit-method)
 
## Laravel 8
If you are considering use Pigeon Version 2.x you does need laravel 8.

## Change emmit method
Method name `emmit` has changed to `dispatch`, upade all `Pigeon::emmit()` to `Pigeon::dispatch()`
 