var WebSocket = require("ws");

var server = new WebSocket.Server({ port: 8080 });

server.on('connection', function (ws) {
    console.log('New client !');

    ws.on('message', function (message) {
        console.log('Incoming message : ', message);
    });

    ws.on('close', function close() {
        console.log('disconnected');
    });

    setTimeout(function () {
        ws.send('world');
    }, 5000);
});
