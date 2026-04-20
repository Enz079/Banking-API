<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/TransactionsController.php';

$app = AppFactory::create();

$app->get('/test', function ($req, $res) {
    $res->getBody()->write(json_encode(["msg" => "ciao"]));
    return $res->withHeader('Content-Type', 'application/json');
});

//Movimenti
$app->get('/accounts/{id}/transactions', "TransactionsController:list");
$app->get('/accounts/{id}/transactions/{idT}', "TransactionsController:detail");

$app->post('/accounts/{id}/deposits', "TransactionsController:deposit");
$app->post('/accounts/{id}/withdrawals', "TransactionsController:withdrawal");

$app->put('/accounts/{id}/transactions/{idT}', "TransactionsController:update");
$app->delete('/accounts/{id}/transactions/{idT}', "TransactionsController:delete");

//Saldo
$app->get('/accounts/{id}/balance', "TransactionsController:balance");

//Conversioni
$app->get('/accounts/{id}/balance/convert/fiat', "TransactionsController:convertToFiat");
$app->get('/accounts/{id}/balance/convert/crypto', "TransactionsController:convertToCrypto");

$app->run();