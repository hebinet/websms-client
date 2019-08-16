<?php namespace WebSms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WebSms\Exception\ParameterValidationException;
use WebSms\TextMessage;

class MessageTest extends TestCase
{

    public function testValidRecipient()
    {
        try {
            $message = new TextMessage([], 'Message');
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('Missing recipients', $e->getMessage());
        }

        try {
            $message = new TextMessage(['test'], 'Message');
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('must be numeric', $e->getMessage());
        }

        try {
            $message = new \WebSms\TextMessage(['067612345678'], 'Message');
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('max. 15 digits', $e->getMessage());
        }

        try {
            $message = new \WebSms\TextMessage(['436761234567891011'], 'Message');
            $this->fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            $this->assertContains('max. 15 digits', $e->getMessage());
        }
    }
}