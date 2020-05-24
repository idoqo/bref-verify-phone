<?php
use App\JsonParserMiddleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client;

require_once __DIR__."/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

$app = AppFactory::create();
$app->addMiddleware(new JsonParserMiddleware());
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType("application/json");

$app->get('/', function (Request $request, Response $response) use ($app) {
    $payload = json_encode(["message" => "I am alive!"]);
    $response->getBody()->write($payload);
    return $response
       ->withHeader("Content-type", "application/json")
       ->withStatus(200);
});

/*
 * POST /send
 * @param string phone E.164 formatted phone number of the recipient
 */
$app->post("/send", function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $payload = [];
    $statusCode = 200;
    if ($data['phone'] == null) {
        $payload['error'] = "Phone is required";
        return response($response, $payload, 422);
    }
    $verifier = getVerifyService();
    try {
        $attempt = $verifier->verifications->create($data['phone'], 'sms');
        $payload['message'] = "Token sent";
        $payload['sid'] = $attempt->sid;
    } catch (\Twilio\Exceptions\TwilioException $e) {
        $payload['error'] = $e->getMessage();
        $statusCode = 400;
    }
    return response($response, $payload, $statusCode);
});

/*
 * POST /verify
 * @param string phone E.164 formatted phone number of the recipient
 * @param string token The received token as entered by the recipient
 */
$app->post("/verify", function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $payload = [];
    $statusCode = 200;
    if ($data['phone'] == null || $data['token'] == null) {
        $payload['error'] = "Phone and token fields are required";
        return response($response, $payload, 422);
    }
    $verifier = getVerifyService();
    try {
        $attempt = $verifier->verificationChecks->create(
            $data['token'],
            ['to' => $data['phone']]
        );
        if ($attempt->valid) {
            $payload['message'] = "Verified!";
        } else {
            $payload['message'] = $attempt->status;
            $payload['data'] = $attempt;
        }
    } catch (\Twilio\Exceptions\TwilioException $e) {
        $statusCode = 500;
        $payload['error'] = $e->getMessage();
    }
    return response($response, $payload, $statusCode);
});

function response(Response $response, array $payload, $statusCode = 200) {
    $response->getBody()->write(json_encode($payload));
    return $response
        ->withHeader("Content-type", "application/json")
        ->withStatus($statusCode);
}

function getVerifyService() {
    $token = getenv("TWILIO_AUTH_TOKEN");
    $sid = getenv("TWILIO_ACCOUNT_SID");
    $verifySid = getenv("TWILIO_VERIFY_SID");
    $twilio = new Client($sid, $token);
    return $twilio->verify->v2->services($verifySid);
}

$app->run();

