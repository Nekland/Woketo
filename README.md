Woketo
======

**A websocket library designed to be released.**

[![Build Status](https://travis-ci.org/Nekland/Woketo.svg?branch=master)](https://travis-ci.org/Nekland/Woketo)

### TODO

- [x] Handcheck
- [x] Reading messages/frames
- [x] Writing messages
- [ ] Cleaning code and design complete user API
- [ ] Add websocket client API
- [ ] Pass wstest

Requirements
------------

* PHP 7+

How to install
--------------

```bash
# The installation is pretty much easier with composer. But you still can use it as git submodule !
composer require "nekland/woketo"
```

How to test
-----------

### Unit tests suite

```
git clone woketo
./bin/phpunit

```


### Functionnal tests suite

```
php tests.php
wstest -m fuzzingclient
```

