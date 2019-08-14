<?php namespace WebSms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WebSms\AuthenticationMode;
use WebSms\Client;
use WebSms\Exception\ParameterValidationException;

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
}