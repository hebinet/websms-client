<?php

use GuzzleHttp\HandlerStack;
use WebSms\AuthenticationMode;
use WebSms\Client;
use WebSms\Exception\ApiException;
use WebSms\Exception\AuthorizationFailedException;
use WebSms\Exception\HttpConnectionException;
use WebSms\Exception\ParameterValidationException;
use WebSms\Exception\UnknownResponseException;
use WebSms\Tests\Traits\HandlerStackTrait;
use WebSms\TextMessage;

uses(HandlerStackTrait::class);

it('can construct the client', function(array $arguments, string $exceptionMessage) {
    $this->expectExceptionMessageMatches("/.*?{$exceptionMessage}.*?/i");

    new Client(...$arguments);
})->expectException(ParameterValidationException::class)
  ->with([
      'too less characters for host' => [['', '1234', 'wert'], 'Hostname in wrong format'],
      'empty username + password' => [['https://api.websms.com/', '', ''], 'Check username\/password or token'],
      'empty password' => [['https://api.websms.com/', 'test', ''], 'Check username\/password or token'],
      'empty username' => [['https://api.websms.com/', '', 'wert'], 'Check username\/password or token'],
      'empty token' => [
          ['https://api.websms.com/', '', null, AuthenticationMode::ACCESS_TOKEN],
          'Check username\/password or token',
      ],
  ]);

it('can parse the url correctly', function() {
    expect(new Client('https://api.websms.com/', 'test', '123456'))
        ->getScheme()->toBe('https')
        ->getHost()->toBe('api.websms.com')
        ->getPath()->toBe('')
        ->getPort()->toBe(443);

    expect(new Client('http://api.websms.com/', 'test', '123456'))
        ->getScheme()->toBe('http')
        ->getPort()->toBe(80);

    expect(new Client('api.websms.com/', 'test', '123456'))
        ->getScheme()->toBe('https')
        ->getPort()->toBe(443);
});

it('throws an error if max sms is lower or equal to 0', function() {
    $client = new Client('https://api.websms.com/', 'test', '123456');

    $client->send(
        new TextMessage(['4367612345678'], 'Message'),
        0
    );
})->expectException(ParameterValidationException::class)
  ->expectExceptionMessageMatches('/less or equal to 0/i');

it('can handle exceptions on send', function(string $exceptionMessage, string $exceptionClass, HandlerStack $stack) {
    $client = new Client('https://api.websms.com/', 'test', '123456');
    $client->setGuzzleOptions(['handler' => $stack]);

    $this->expectException($exceptionClass);
    $this->expectExceptionMessageMatches("/.*?{$exceptionMessage}.*?/i");

    $client->send(new TextMessage(['4367612345678'], 'Message'), 1);
})->with([
    'couldn\'t connect' => ['HTTP Status: 0', HttpConnectionException::class, fn() => $this->couldntConnectHandler()],
    'authentication failed' => [
        'Basic Authentication failed',
        AuthorizationFailedException::class,
        fn() => $this->authenticationFailedHandler(),
    ],
    'http error' => ['HTTP Status: 204', HttpConnectionException::class, fn() => $this->httpErrorHandler()],
    'no json response' => [
        'unknown content type',
        UnknownResponseException::class,
        fn() => $this->noJsonResponseHandler(),
    ],
    'wrong api status code' => ['Some error appeared', ApiException::class, fn() => $this->wrongApiStatusCodeHandler()],
]);

it('can send', function() {
    $client = new Client('https://api.websms.com/', 'test', '123456');
    $client->setGuzzleOptions([
        'handler' => $this->successHandler(),
    ]);

    // Success
    $response = $client->send(new TextMessage(['4367612345678'], 'Message'), 1);

    expect($response)
        ->not->toBeNull()
             ->getApiStatusCode()->toBe(2000)
             ->getApiStatusMessage()->toBe('OK')
             ->getTransferId()->toBe('12345')
             ->getClientMessageId()->toBe('67890');
});
