const WebSocket = require('ws');
const fs = require('fs');

var messages = fs.readFileSync('../data/messages.json', 'utf-8');
messages = JSON.parse(messages);

var server = new WebSocket.Server({ port: 9003 });

server.on('connection', function connection(ws) {

    ws.on('message', function incoming(data) {
        messages.forEach((item) => {
            if (item.message === data) {
                ws.send(item.response)
            }
        });
    });

    ws.on('close', function close() {
        //console.log('disconnection');
    });

    //console.log('connection');
});
