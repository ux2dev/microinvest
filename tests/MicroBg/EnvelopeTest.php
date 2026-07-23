<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\MicroBg\Envelope;

it('accepts both the status and the success flag', function (array $envelope) {
    expect(Envelope::unwrap($envelope, 'getItems'))->toBe([['id' => 1]]);
})->with([
    [['status' => 1, 'errors' => [], 'data' => [['id' => 1]]]],
    [['status' => true, 'errors' => [], 'data' => [['id' => 1]]]],
    [['success' => true, 'errors' => [], 'data' => [['id' => 1]]]],
]);

it('returns null for a method that reports no data', function () {
    expect(Envelope::unwrap(['status' => 1, 'errors' => [], 'data' => null], 'deleteItem'))->toBeNull();
});

it('turns a failed envelope into an ApiException carrying the errors', function () {
    $envelope = ['status' => 0, 'errors' => ['Invalid code', 'Partner not found'], 'data' => null];

    expect(fn () => Envelope::unwrap($envelope, 'insertPartner'))
        ->toThrow(ApiException::class, 'Invalid code; Partner not found');

    try {
        Envelope::unwrap($envelope, 'insertPartner');
    } catch (ApiException $e) {
        expect($e->httpStatus)->toBe(200)
            ->and($e->apiMessage)->toBe('Invalid code; Partner not found')
            ->and($e->body['errors'])->toBe(['Invalid code', 'Partner not found']);
    }
});

it('names the function when the failure carries no errors', function () {
    expect(fn () => Envelope::unwrap(['status' => false, 'errors' => []], 'saveOperation'))
        ->toThrow(ApiException::class, 'micro.bg rejected saveOperation');
});

it('accepts a plain string in the errors slot', function () {
    expect(fn () => Envelope::unwrap(['status' => 0, 'errors' => 'boom'], 'getItems'))
        ->toThrow(ApiException::class, 'boom');
});

it('ignores an empty string in the errors slot', function () {
    expect(fn () => Envelope::unwrap(['status' => 0, 'errors' => ''], 'getItems'))
        ->toThrow(ApiException::class, 'micro.bg rejected getItems');
});

it('stringifies non-string error entries', function () {
    expect(fn () => Envelope::unwrap(['status' => 0, 'errors' => [['code' => 7]]], 'getItems'))
        ->toThrow(ApiException::class, '{"code":7}');
});

it('reads a real rejection from micro.bg', function () {
    // Captured from a live call on 2026-07-23. Confirms three things the PDF
    // left ambiguous: the ok flag is spelled `status` (not `success`), it is a
    // real boolean, and `errors` is a list of plain strings.
    $envelope = ['status' => false, 'errors' => ['_INVALID_API_ID'], 'data' => null];

    try {
        Envelope::unwrap($envelope, 'getCompanyData');
        expect()->fail('the envelope should have been rejected');
    } catch (ApiException $e) {
        expect($e->getMessage())->toBe('_INVALID_API_ID')
            ->and($e->apiMessage)->toBe('_INVALID_API_ID')
            ->and($e->httpStatus)->toBe(200)
            ->and($e->body['data'])->toBeNull();
    }
});

it('rejects an envelope with neither flag', function () {
    expect(fn () => Envelope::unwrap(['data' => []], 'getItems'))
        ->toThrow(InvalidResponseException::class, 'neither a status nor a success flag');
});
