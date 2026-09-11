<?php

use Tmoh\DjomyPayment\Signature;

it('generates a valid HMAC-SHA256 hexadecimal signature', function () {
    $stringToSign = 'merchant-123';
    $clientSecret = 'sup3r-secret';
    $expected = hash_hmac('sha256', $stringToSign, $clientSecret);

    expect(Signature::hash($stringToSign, $clientSecret))->toBe($expected);
});

it('returns a lowercase hexadecimal string', function () {
    $signature = Signature::hash('merchant-123', 'secret');

    expect($signature)
        ->toBeString()
        ->toHaveLength(64)
        ->toMatch('/^[a-f0-9]{64}$/');
});

it('matches the reference Laravel/PHP implementation', function () {
    $signature = Signature::hash('client-id', 'client-secret');

    expect($signature)->toBe(hash_hmac('sha256', 'client-id', 'client-secret'));
});

it('builds the X-API-KEY header in the expected format', function () {
    $headerValue = Signature::apiKeyHeaderValue('client-id', 'client-secret');
    $expected = 'client-id:'.hash_hmac('sha256', 'client-id', 'client-secret');

    expect($headerValue)->toBe($expected);
});

it('produces a different signature for different keys', function () {
    expect(Signature::hash('key-a', 'secret'))
        ->not->toBe(Signature::hash('key-b', 'secret'));
});
