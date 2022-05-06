<?php

use PHPUnit\Framework\TestCase;
use WebSms\Exception\ParameterValidationException;
use WebSms\TextMessage;

it('can validate the recipient list', function (array $recipients, string $exceptionMessage) {
    $this->expectException(ParameterValidationException::class);
    $this->expectExceptionMessageMatches("/.*?{$exceptionMessage}.*?/i");

    new TextMessage($recipients, 'Message');
})->with([
    'empty recipients' => [[], 'Missing recipients'],
    'text recipients' => [['test'], 'must be numeric'],
    'max 15 numbers with leading 0' => [['067612345678'], 'max. 15 digits'],
    'max 15 numbers with leading country code' => [['436761234567891011'], 'max. 15 digits'],
]);
