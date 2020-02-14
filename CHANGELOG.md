# Pigeon changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Added hability to test for timeout on consumers
- Added hability to test for comsmer multiplicit
### Changed
- Catch `Throwble` instead of `Exception` on default fallback
### Fixed
- Fixed support for Laravel 6

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
- Add `application_headers` to `Pigeon::emmit` as last parameter
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
