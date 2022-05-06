<?php

namespace WebSms\Tests\Unit;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WebSms\AuthenticationMode;
use WebSms\Client;
use WebSms\Exception\ApiException;
use WebSms\Exception\AuthorizationFailedException;
use WebSms\Exception\HttpConnectionException;
use WebSms\Exception\ParameterValidationException;
use WebSms\Exception\UnknownResponseException;
use WebSms\TextMessage;

class ClientTest extends TestCase
{
    public function testConstruct(): void
    {
        try {
            new Client('', '1234', 'wert');

            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('Hostname in wrong format', $e->getMessage());
        }

        try {
            new Client('https://api.websms.com/', '', '');
            new Client('https://api.websms.com/', 'test', '');
            new Client('https://api.websms.com/', '', 'wert');
            new Client('https://api.websms.com/', '', null, AuthenticationMode::ACCESS_TOKEN);

            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('Check username/password or token', $e->getMessage());
        }
    }

    public function testUrlInit(): void
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');

        self::assertEquals('https', $client->getScheme());
        self::assertEquals('api.websms.com', $client->getHost());
        self::assertEquals('', $client->getPath());
        self::assertEquals(443, $client->getPort());


        $client = new Client('http://api.websms.com/', 'test', '123456');

        self::assertEquals('http', $client->getScheme());
        self::assertEquals(80, $client->getPort());


        $client = new Client('api.websms.com/', 'test', '123456');

        // defaults to https
        self::assertEquals('https', $client->getScheme());
        self::assertEquals(443, $client->getPort());
    }

    public function testMaxSmsPerMessage(): void
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');

        try {
            $message = null;
            try {
                $message = new TextMessage(['4367612345678'], 'Message');
            } catch (ParameterValidationException $e) {
                self::fail('ParameterValidationException thrown.');
            }
            $client->send($message, 0);
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('less or equal to 0', $e->getMessage());
        }
    }

    public function testSend(): void
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');
        $client->setGuzzleOptions(
            [
                'handler' => $this->getMockHandlers(),
            ]
        );

        try {
            $message = null;
            try {
                $message = new TextMessage(['4367612345678'], 'Message');
            } catch (ParameterValidationException $e) {
                self::fail('ParameterValidationException thrown.');
            }

            try {
                // couldn't connect
                $client->send($message, 1);
                self::fail('HttpConnectionException not thrown.');
            } catch (HttpConnectionException $e) {
                self::assertStringContainsStringIgnoringCase('HTTP Status: 0', $e->getMessage());
            }

            try {
                // Authentication failed Username/Password
                $client->send($message, 1);
                self::fail('AuthorizationFailedException not thrown.');
            } catch (AuthorizationFailedException $e) {
                self::assertStringContainsStringIgnoringCase('Basic Authentication failed', $e->getMessage());
            }

            try {
                // HTTP Error
                $client->send($message, 1);
                self::fail('HttpConnectionException not thrown.');
            } catch (HttpConnectionException $e) {
                self::assertStringContainsStringIgnoringCase('HTTP Status: 204', $e->getMessage());
            }

            try {
                // No JSON Response
                $client->send($message, 1);
                self::fail('UnknownResponseException not thrown.');
            } catch (UnknownResponseException $e) {
                self::assertStringContainsStringIgnoringCase('unknown content type', $e->getMessage());
            }

            try {
                // Wrong API status code
                $client->send($message, 1);
                self::fail('ApiException not thrown.');
            } catch (ApiException $e) {
                self::assertStringContainsStringIgnoringCase('Some error appeared', $e->getMessage());
            }

            try {
                // Success
                $response = $client->send($message, 1);

                self::assertEquals(2000, $response->getApiStatusCode());
                self::assertEquals('OK', $response->getApiStatusMessage());
                self::assertEquals('12345', $response->getTransferId());
                self::assertEquals('67890', $response->getClientMessageId());
            } catch (ApiException $e) {
                self::fail('ApiException thrown.');
            } catch (AuthorizationFailedException $e) {
                self::fail('AuthorizationFailedException thrown.');
            } catch (HttpConnectionException $e) {
                self::fail('HttpConnectionException thrown.');
            } catch (UnknownResponseException $e) {
                self::fail('UnknownResponseException thrown.');
            }
        } catch (ParameterValidationException $e) {
            self::fail('ParameterValidationException thrown.');
        }
    }

    protected function getMockHandlers(): HandlerStack
    {
        $wrongApiStatusCode = new \stdClass();
        $wrongApiStatusCode->statusCode = 1999;
        $wrongApiStatusCode->statusMessage = 'Some error appeared';

        $successfulResponse = new \stdClass();
        $successfulResponse->statusCode = 2000;
        $successfulResponse->statusMessage = 'OK';
        $successfulResponse->transferId = '12345';
        $successfulResponse->clientMessageId = '67890';

        $mock = new MockHandler(
            [
                // couldn't connect
                new RequestException("Error Communicating with Server", new Request('GET', 'test')),
                // Authentication failed
                new Response(401, []),
                //new Response(401, []),
                // HTTP Error
                new Response(204, ['Content-Type' => 'application/json']),
                // No JSON Response
                new Response(200, ['Content-Type' => 'text/plain'], 'test'),
                // Wrong API status code
                new Response(200, ['Content-Type' => 'application/json'],
                    json_encode($wrongApiStatusCode, JSON_THROW_ON_ERROR)),

                // Success
                new Response(200, ['Content-Type' => 'application/json'],
                    json_encode($successfulResponse, JSON_THROW_ON_ERROR)),

            ]
        );

        return HandlerStack::create($mock);
    }
}
