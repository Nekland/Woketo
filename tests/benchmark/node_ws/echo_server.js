const WebSocket = require('ws');

var server = new WebSocket.Server({ port: 9002 });

server.on('connection', function connection(ws) {

    ws.on('message', function incoming(data) {
        ws.send(data);
    });

    ws.on('close', function close() {
        //console.log('disconnection');
    });

    //console.log('connection');
});
