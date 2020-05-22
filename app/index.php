<?php
use App\JsonParserMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__."/../vendor/autoload.php";

$app = AppFactory::create();
$app->addMiddleware(new JsonParserMiddleware());
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType("application/json");

$app->get('/', function (Request $request, Response $response) {
    $payload = json_encode(["health" => "I am alive!"]);
    $response->getBody()->write($payload);
    return $response
       ->withHeader("Content-type", "application/json")
       ->withStatus(200);
});

$app->post("/send", function (Request $request, Response $response) {
    $data = $request->getParsedBody() != null ? $request->getParsedBody() : [];
    $data['routes'] = "send";
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response
        ->withHeader("Content-type", "application/json")
        ->withStatus(200);
});

$app->post("/resend", function (Request $request, Response $response) {
    $data = $request->getParsedBody() != null ? $request->getParsedBody() : [];
    $data['routes'] = "resend";
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response
        ->withHeader("Content-type", "application/json")
        ->withStatus(200);
});

$app->post("/verify", function (Request $request, Response $response) {
    $data = $request->getParsedBody() != null ? $request->getParsedBody() : [];
    $data['routes'] = "verify";
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response
        ->withHeader("Content-type", "application/json")
        ->withStatus(200);
});

$app->post("/cancel", function (Request $request, Response $response) {
    $data = $request->getParsedBody() != null ? $request->getParsedBody() : [];
    $data['routes'] = "verify";
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response
        ->withHeader("Content-type", "application/json")
        ->withStatus(200);
});

$app->run();

