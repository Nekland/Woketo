# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).


## [Unreleased]

## [2.2.0] - 2018-10-04
### Added
- `onDisconnect` method could be implemented in a MessageHandler. This method is called when the connection between client and server is resume
### Changed
- Allow nekland/tools in 2.0 version (still works with 1.0)

## [2.1.0] - 2018-08-02
### Changed
- Update of depedencies, no compatibility break on Woketo API
- Update of PHPUnit and the test suite to have better tests

## [2.0.2] - 2018-06-04
### Fixed
- Fix ability to specify hostname of the server on non secured protocol

## [2.0.1] - 2018-02-11
### Added
- Add possibility to retrieve the loop #133

### Fixed
- Fix wrong doc about woketo client

## [2.0.0] - 2017-08-25
### Added
- `WebSocketClient` class that allows you to dial with a standard WebSocket server
- Javascript server that proves that woketo server works with anything (not just autobahn testsuite)

### Changed
- [BC Break] Message handlers now use an `AbstractConnection` class in their methods
- **Internal:** the method `processHandcheck` is now `processHandshake` and follows `AbstractConnection` class requirements
- **Internal:** the `MessageProcessor` now needs to be aware of its quality of client or server.
- **Internal:** the `BitManipulation` class now uses PHP native functions
- **Internal:** the `BitManipulation` class uses new method names


## [1.1.0] - 2017-01-24
### Added
- Support for different handlers depending on specified URI in the request
- Add wss support

### Changed
- [BC Break] in the internal API, the "Connection" signature changed. This should not impact any user though 
- react socket minimum version required upgraded (no bc break)

## [1.0.0] - 2017-01-03
### Added
- First release
- WebSockets server
- Passes autobahn test suite
