<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\MicroBg\RequestSigner;

it('signs the encoded string, not the raw json', function () {
    $payload = ['functionName' => 'getPartners', 'parameters' => [], 'functionData' => null];

    $fields = (new RequestSigner('api-id', 'secret'))->sign($payload);

    $expectedEncoded = urlencode(base64_encode(json_encode($payload)));
    $expectedHash = hash_hmac('sha256', $expectedEncoded, 'secret');

    expect($fields['ApiId'])->toBe('api-id')
        ->and($fields['Request'])->toBe($expectedEncoded . $expectedHash);
});

it('appends a 64 character hex hash', function () {
    $fields = (new RequestSigner('api-id', 'secret'))->sign(['functionName' => 'getItems']);

    expect(substr($fields['Request'], -64))->toMatch('/^[0-9a-f]{64}$/');
});

it('round-trips the payload through base64', function () {
    $payload = ['functionName' => 'insertPartner', 'parameters' => ['AutoGenerateCode' => 1]];

    $request = (new RequestSigner('api-id', 'secret'))->sign($payload)['Request'];

    $encoded = substr($request, 0, -64);

    expect(json_decode(base64_decode(urldecode($encoded)), true))->toBe($payload);
});

it('keeps float prices as floats on the wire', function () {
    $request = (new RequestSigner('a', 'b'))->sign(['functionData' => ['PriceOut1' => 12.0]])['Request'];

    expect(base64_decode(urldecode(substr($request, 0, -64))))->toContain('12.0');
});

it('wraps a json encoding failure', function () {
    // Invalid UTF-8 cannot be JSON encoded, so the payload never reaches the wire.
    expect(fn () => (new RequestSigner('a', 'b'))->sign(['functionData' => "\xB1\x31\x00"]))
        ->toThrow(InvalidResponseException::class, 'Failed to encode micro.bg request payload');
});

it('produces a different signature for a different secret', function () {
    $payload = ['functionName' => 'getItems'];

    expect((new RequestSigner('a', 'secret-one'))->sign($payload)['Request'])
        ->not->toBe((new RequestSigner('a', 'secret-two'))->sign($payload)['Request']);
});
