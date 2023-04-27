# Pigeon changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
## [v2.2.1]
### Removed
- Remove webpatser/uuid dependency.
### Changed
- Change correlation_id uuid to be generated with Str::uuid helper

## [v2.2.0]
### Added
- Support Laravel 10.
- Support PHP 8.2.
- Function Pigeon::assertNothingDispatched().
- Function Pigeon::assertDispatchedCount(int $count).

## [v2.1.0]
### Added
- Laravel 9 support
- php 8.1 support

## [v2.0.0]
### Fixed
- Properties on publish method
- Reconnect when missed heart beat

### Added
- Laravel 8 support
- Added `Pigeon::assertNotDispatched()` method to Fake driver

### Changed
- Change `emmit` method name to `dispatch`
- Change `assertEmitted` method name to `assertDispatched`
- Property `Convenia\Pigeon\Resolver\Resolver::message` is public now
- Method `Convenia\Pigeon\Publisher\PublisherContract::publish()` now have the second param as a properties array

### Removed
- Methods `Pigeon::rpc`, `Convenia\Pigeon\Resolver::response`, `Pigeon::assertRpc`, `Pigeon::rpcPushed`, `Pigeon::assertCallbackReturn`
- Drop PHP 7.2 support
- Drop Laravel 5.6 support
## [v1.6.0]
### Added
- Added RPCs test
- Added support PHPUnit 8
- Laravel 7 support

## [v1.5.0]
### Added
- Added hability to test for timeout on consumers
- Added hability to test for comsmer multiplicit

### Changed
- Catch `Throwble` instead of `Exception` on default fallback

### Fixed
- Fixed support for Laravel 6
- Fixed facade `dispatch` signature

## [v1.4.1]
### Removed
-  Removed log info from IGNORE precondition

## [v1.4.0]
### Added
- Added hability to send to default exchange when the exchange name is empty in `exchange()` method

### Fixed
- Fixed use of empty string on exchange to use default AMQP queue

## [v1.3.1]
### Added
- Added event name to message header

## [v1.3.0]
### Added
- Added `on-failure` config to ack, reject or throw exception
- Added `Pigeon::headers([])` and config `headers` key
- Added possibility to use callable on headers config

### Fixed
- Fixed failing when not set fallback and throw exception
- Fixed wrong `MessageProcessorTest` tests

## [1.2.0]
### Added
- Added bugs test suite
- Added null driver throw `Convenia\Pigeon\Exceptions\Driver\NullDriverException` exception

### Fixed
- Fix `No free channel id` message when emit large amount of events
- Fix env example

### Changed
- Change default driver to `rabbit`

## [1.2.0-beta.2]
### Fixed
- Fixed `connection closed` sending RPC from consumer

## [1.2.0-beta.1]
### Added
- Add `application_headers` to `Pigeon::dispatch` as last parameter
- Add configurable precondition catch on queue creation

### Fixed
- Auto declare exchange with `routing`
- Fix `application_headers` to `AMQPTable`

## [1.1.0]
### Added
- Laravel 6 support

## [1.0.0]
### Added 
- Added `headers` to `Publisher`
- Added `headers` to `ResolverContract`
- Added `assertEmitted` to `Pigeon::fake()`
- Added `assertConsumingEvent` to `Pigeon::fake()`
- Added `dispatchListener` to `Pigeon::fake()`
- Added dead letter exchange to queue/exchange declare
- Added laravel auto discovery
- Added config as publishable using `pigeon.config`

### Change
- Default timeout from 5 to 0

### Fixed
- Fix acknowledge with fake

### Removed
- Remove `$properties` from `PublisherContract::publish()`, `PublisherContract::rpc()`

## [v1.0.0-alpha-1]
### Fixed
- Fixed `getDefaultDriver` return from config

### Change
- Change event listen wildcard from `*` to `#`
- Change event exchange type from `direct` to `topic`
- Change `php-amqplib` to `>=2.8`

## [v1.0.0-alpha]
### Added
- Message send using default and custom exchange
- Consume a queue
- Remote procedure call
- Event sourcing
- Ack, reject and response a message
