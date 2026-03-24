<?php

declare(strict_types=1);

use Directive\Http\Response\HttpResponse;
use Directive\Http\Response\ResponseEntity;
use Nyholm\Psr7\Factory\Psr17Factory;

function makeHttpResponse(): HttpResponse
{
    $factory = new Psr17Factory();

    return new HttpResponse($factory, $factory);
}

function decodeBody(\Psr\Http\Message\ResponseInterface $response): array
{
    return json_decode((string) $response->getBody(), true);
}

// ------------------------------------------------------------------
// ok() — no errors key
// ------------------------------------------------------------------

it('ok() response does not contain errors key', function () {
    $entity = new ResponseEntity();
    $entity->setData(['id' => 1]);
    $response = makeHttpResponse()->ok($entity, '/test', 'GET');
    $body     = decodeBody($response);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toHaveKey('data');
    expect($body)->not->toHaveKey('errors');
});

// ------------------------------------------------------------------
// badRequest()
// ------------------------------------------------------------------

it('badRequest() with errors contains errors key and data is null', function () {
    $errors   = [['property' => 'email', 'message' => 'Field "email" is required.', 'type' => 'missing']];
    $response = makeHttpResponse()->badRequest('/test', 'POST', $errors);
    $body     = decodeBody($response);

    expect($response->getStatusCode())->toBe(400);
    expect($body['data'])->toBeNull();
    expect($body)->toHaveKey('errors');
    expect($body['errors'])->toHaveCount(1);
    expect($body['errors'][0]['property'])->toBe('email');
    expect($body['errors'][0]['type'])->toBe('missing');
});

it('badRequest() with no errors does not contain errors key', function () {
    $response = makeHttpResponse()->badRequest('/test', 'POST');
    $body     = decodeBody($response);

    expect($response->getStatusCode())->toBe(400);
    expect($body)->not->toHaveKey('errors');
});

// ------------------------------------------------------------------
// unprocessable()
// ------------------------------------------------------------------

it('unprocessable() with errors contains errors key, data is null, status 422', function () {
    $errors   = [['property' => 'quota', 'message' => 'Quota exceeded.', 'type' => 'invalid']];
    $response = makeHttpResponse()->unprocessable('/test', 'POST', $errors);
    $body     = decodeBody($response);

    expect($response->getStatusCode())->toBe(422);
    expect($body['message'])->toBe('Unprocessable Content');
    expect($body['data'])->toBeNull();
    expect($body)->toHaveKey('errors');
    expect($body['errors'])->toHaveCount(1);
    expect($body['errors'][0]['property'])->toBe('quota');
});

it('unprocessable() with no errors does not contain errors key', function () {
    $response = makeHttpResponse()->unprocessable('/test', 'POST');
    $body     = decodeBody($response);

    expect($response->getStatusCode())->toBe(422);
    expect($body)->not->toHaveKey('errors');
});
