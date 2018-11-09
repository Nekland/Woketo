Performance test to compare Woketo and other tools
=================================================


Start projects
--------------


### Woketo echo test

```
cd woketo
php echo_server.php
```

This will open an echo WebSocket server on port 9001.


### nodejs ws echo test

```
cd node_ws
npm install
node echo_server.js
```


This will open an echo WebSocket server on port 9002.


### Ratchet echo test

```
cd ratchet
composer install
php echo_server.php
```

This will open an echo WebSocket server on port 9003.

Run the tests
-------------

```bash
php many_connections.php
```
