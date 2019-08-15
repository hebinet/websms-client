<?php namespace WebSms\Tests\Unit;

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
use function GuzzleHttp\Psr7\stream_for;

class ClientTest extends TestCase
{
    public function testConstruct()
    {
        try {
            $client = new Client('', '1234', 'wert');
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('Hostname in wrong format', $e->getMessage());
        }

        try {
            $client = new Client('https://api.websms.com/', '', '');
            $client = new Client('https://api.websms.com/', 'test', '');
            $client = new Client('https://api.websms.com/', '', 'wert');
            $client = new Client('https://api.websms.com/', '', null, AuthenticationMode::ACCESS_TOKEN);
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('Check username/password or token', $e->getMessage());
        }
    }

    public function testUrlInit()
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');

        $this->assertEquals('https', $client->getScheme());
        $this->assertEquals('api.websms.com', $client->getHost());
        $this->assertEquals('', $client->getPath());
        $this->assertEquals('443', $client->getPort());


        $client = new Client('http://api.websms.com/', 'test', '123456');

        $this->assertEquals('http', $client->getScheme());
        $this->assertEquals('80', $client->getPort());


        $client = new Client('api.websms.com/', 'test', '123456');

        // defaults to https
        $this->assertEquals('https', $client->getScheme());
        $this->assertEquals('443', $client->getPort());
    }

    public function testMaxSmsPerMessage()
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');

        try {
            $message = null;
            try {
                $message = new TextMessage(['4367612345678'], 'Message');
            } catch (ParameterValidationException $e) {
                $this->fail('ParameterValidationException thrown.');
            }
            $client->send($message, 0, true);
        } catch (ParameterValidationException $e) {
            $this->assertContains('less or equal to 0', $e->getMessage());
        }
    }

    public function testSend()
    {
        $client = new Client('https://api.websms.com/', 'test', '123456');
        $client->setGuzzleOptions([
            'handler' => $this->getMockHandlers()
        ]);

        try {
            $message = null;
            try {
                $message = new TextMessage(['4367612345678'], 'Message');
            } catch (ParameterValidationException $e) {
                $this->fail('ParameterValidationException thrown.');
            }

            try {
                // couldn't connect
                $client->send($message, 1, true);
                $this->fail('HttpConnectionException not thrown.');
            } catch (HttpConnectionException $e) {
                $this->assertContains('HTTP Status: 0', $e->getMessage());
            }

            try {
                // Authentication failed Username/Password
                $client->send($message, 1, true);
                $this->fail('AuthorizationFailedException not thrown.');
            } catch (AuthorizationFailedException $e) {
                $this->assertContains('Basic Authentication failed', $e->getMessage());
            }

            try {
                // HTTP Error
                $client->send($message, 1, true);
                $this->fail('HttpConnectionException not thrown.');
            } catch (HttpConnectionException $e) {
                $this->assertContains('HTTP Status: 204', $e->getMessage());
            }

            try {
                // No JSON Response
                $client->send($message, 1, true);
                $this->fail('UnknownResponseException not thrown.');
            } catch (UnknownResponseException $e) {
                $this->assertContains('unknown content type', $e->getMessage());
            }

            try {
                // Wrong API status code
                $client->send($message, 1, true);
                $this->fail('ApiException not thrown.');
            } catch (ApiException $e) {
                $this->assertContains('Some error appeared', $e->getMessage());
            }

            try {
                // Success
                $response = $client->send($message, 1, true);

                $this->assertEquals(2000, $response->getApiStatusCode());
                $this->assertEquals('OK', $response->getApiStatusMessage());
                $this->assertEquals('12345', $response->getTransferId());
                $this->assertEquals('67890', $response->getClientMessageId());

            } catch (ApiException $e) {
                $this->fail('ApiException thrown.');
            } catch (AuthorizationFailedException $e) {
                $this->fail('AuthorizationFailedException thrown.');
            } catch (HttpConnectionException $e) {
                $this->fail('HttpConnectionException thrown.');
            } catch (UnknownResponseException $e) {
                $this->fail('UnknownResponseException thrown.');
            }
        } catch (ParameterValidationException $e) {
            $this->fail('ParameterValidationException thrown.');
        }
    }

    /**
     * @return HandlerStack
     */
    private function getMockHandlers()
    {
        $wrongApiStatusCode = new \stdClass();
        $wrongApiStatusCode->statusCode = 1999;
        $wrongApiStatusCode->statusMessage = 'Some error appeared';

        $successfulResponse = new \stdClass();
        $successfulResponse->statusCode = 2000;
        $successfulResponse->statusMessage = 'OK';
        $successfulResponse->transferId = '12345';
        $successfulResponse->clientMessageId = '67890';

        $mock = new MockHandler([
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
            new Response(200, ['Content-Type' => 'application/json'], json_encode($wrongApiStatusCode)),

            // Success
            new Response(200, ['Content-Type' => 'application/json'], json_encode($successfulResponse))

        ]);

        return HandlerStack::create($mock);
    }
}