# Pigeon changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Change
- Default timeout from 5 to 0

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
