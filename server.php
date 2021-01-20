<?php

include 'vendor/autoload.php';
include './timer.php';

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Socket\Server;
use Psr\Log\NullLogger;

// Run this script, then visit http://localhost:9001/ in your browser.

Amp\Loop::run(function () {
  $sockets = [
    Server::listen("0.0.0.0:9001"),
    Server::listen("[::]:9001"),
  ];

  $timers = [];

  $router = new Router();
  $router->addRoute('POST', '/', new CallableRequestHandler(function () use (&$timers) {
    $timer = new Timer();
    $timer->startTime();

    $timers[] = $timer;
    $position = count($timers) - 1;

    return new Response(Status::OK, ['content-type' => 'text/plain'], "Position: $position");
  }));

  $router->addRoute('GET', '/{id}', new CallableRequestHandler(function (Request $request) use (&$timers) {
    $args = $request->getAttribute(Router::class);

    if (array_key_exists($args['id'], $timers)) {
      $id = $args['id'];
      $time = $timers[$id]->getTime()->format('%h:%i:%s');

      return new Response(Status::OK, ['content-type' => 'text/plain'], "Running for {$time}");

    } else {
      return new Response(Status::NOT_FOUND, ['content-type' => 'text/plain'], "No timer found!");

    }
  }));

  $server = new HttpServer($sockets, $router, new NullLogger);

  yield $server->start();

  // Stop the server gracefully when SIGINT is received.
  // This is technically optional, but it is best to call Server::stop().
  Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
    Amp\Loop::cancel($watcherId);
    yield $server->stop();
  });
});
