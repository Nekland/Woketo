# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- `WebSocketClient` class that allows you to dial with a standard WebSocket server
- Add client handshake

### Changed
- Deprecate old `ServerHandshake` in favor of an implementation of `HandshakeInterface`
- **Minor BC Break:** the method `processHandcheck` is now `processHandshake` and follows `AbstractConnection` class requirements
- **Major BC Break:** the `Connection` must now be type `AbstractConnection`


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
