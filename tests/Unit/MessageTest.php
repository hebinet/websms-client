<?php

use WebSms\Exception\ParameterValidationException;
use WebSms\TextMessage;

it('can validate the recipient list', function(array $recipients, string $exceptionMessage) {
    $this->expectException(ParameterValidationException::class);
    $this->expectExceptionMessageMatches("/.*?{$exceptionMessage}.*?/i");

    new TextMessage($recipients, 'Message');
})->with([
    'empty recipients' => [[], 'Missing recipients'],
    'text recipients' => [['test'], 'must be numeric'],
    'max 15 numbers with leading 0' => [['067612345678'], 'max. 15 digits'],
    'max 15 numbers with leading country code' => [['436761234567891011'], 'max. 15 digits'],
]);

it('can calculate the correct message count in text messages', function(int $count, string $message) {
    expect(new TextMessage(['4367612345678'], $message))
        ->getMessageCount()->toBe($count);
})->with([
    'empty string' => [1, ''],
    'standard message' => [1, 'Hallo'],
    '1 message just on the edge' => [1, str_repeat('x', 160)],
    '2 messages' => [2, str_repeat('x', 161)],
]);
