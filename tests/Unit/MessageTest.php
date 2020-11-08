<?php

namespace WebSms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WebSms\Exception\ParameterValidationException;
use WebSms\TextMessage;

class MessageTest extends TestCase
{
    public function testValidRecipient(): void
    {
        try {
            new TextMessage([], 'Message');
            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('Missing recipients', $e->getMessage());
        }

        try {
            new TextMessage(['test'], 'Message');
            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('must be numeric', $e->getMessage());
        }

        try {
            new TextMessage(['067612345678'], 'Message');
            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('max. 15 digits', $e->getMessage());
        }

        try {
            new TextMessage(['436761234567891011'], 'Message');
            self::fail('ParameterValidationException not thrown.');
        } catch (ParameterValidationException $e) {
            self::assertStringContainsStringIgnoringCase('max. 15 digits', $e->getMessage());
        }
    }
}
