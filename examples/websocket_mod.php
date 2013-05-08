<?php

/**
 * examples/websocket_mod.php
 * 
 * While an Aerys host can serve strictly ws:// or wss:// resources, it's often useful to layer 
 * endpoints on top of your regular application handler. To accomplish this we simply register the 
 * built-in websocket mod for the relevant host. Now the requests for the URI(s) specified by the
 * websocket mod will be caught and handled by the specified websocket endpoints instead of being
 * handled by the normal application callable.
 * 
 * In this example we serve static files from a specified document root for all requests but register
 * the websocket mod to intercept requests made to the `/chat` URI and process them using our
 * `ExampleChatEndpoint` class.
 * 
 * To run:
 * 
 * $ php websocket_mod.php
 * 
 * Once the server has started, request http://127.0.0.1:1337/ in your browser or client of choice.
 * If you open the address in multiple tabs you'll see that your data is passed back and forth via
 * the websocket application!
 */

use Aerys\Config\DocRootLauncher,
    Aerys\Config\Configurator;

require dirname(__DIR__) . '/autoload.php';
require __DIR__ . '/support/ExampleChatEndpoint.php';

(new Configurator)->createServer([[
    'listenOn'      => '*:1337',
    'application'   => new DocRootLauncher([
        'docRoot'   => __DIR__ . '/support/websocket_mod_root'
    ]),
    'mods' => [
        'websocket' => [
            '/chat' => new ExampleChatEndpoint
        ],
        'log' => [
            'php://stdout' => 'common'
        ]
    ]
]])->start();

