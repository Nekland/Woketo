<?php

// Take first parameter to define the port
// Uses help function to render help if needed

$tool = 'woketo';
$port = 9001;
if ($argc > 1) {
    switch ($argv[1]) {
        case 'woketo':
            $port = 9001;
            break;
        case 'ratchet':
            $port = 9002;
            $tool = 'ratchet';
            break;
        case 'node_ws':
        case 'ws':
        case 'node':
            $port = 9003;
            $tool = 'nodejs WS';
            break;
        default:
            help($times);
    }
} else {
    help($times);
}
