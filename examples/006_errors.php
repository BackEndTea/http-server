<?php

/**
 * Aerys insulates itself from fatal application errors by monitoring worker processes and
 * respawning servers as needed. This example demonstrates what happens when your application
 * triggers a fatal error. To generate this error, simply load this URI in your browser after
 * launching the server:
 *
 *     http://127.0.0.1:1337/fatal
 *
 * Servers also generate a 500 response if your application throws an uncaught exception while
 * responding to a request. This behavior is demonstrated here:
 *
 *     http://127.0.0.1:1337/exception
 *
 * Note that error traceback is displayed by default. This behavior can be controlled using the
 * server's "showErrors" option. Production servers should set this value to FALSE to avoid
 * displaying error output to end-users.
 *
 * To run:
 *
 * $ bin/aerys -c examples/006_errors.php --debug
 *
 * Once started, load http://127.0.0.1:1337/ in your browser.
 */

$myApp = (new Aerys\HostConfig)->setPort(1337)->addResponder(function($request) {
    switch ($request['REQUEST_URI_PATH']) {
        case '/fatal':
            $nonexistentObj->nonexistentMethod(); // <-- will cause a fatal E_ERROR
            break;
        case '/exception':
            throw new \Exception('Test Exception');
            break;
        default:
            $li = '
            <p>This app demonstrates fatal error and exception recovery.</p>
            <ul>
                <li><a href="/fatal">/fatal</a></li>
                <li><a href="/exception">/exception</a></li>
            </ul>';

            return "<html><body><h1>Error Recovery</h1>{$li}</body></html>";
    }
});
