<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\MicroBg\MicroBgTransport;
use Ux2Dev\Microinvest\Tests\Http\FakeClientException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

function fakeMicroBgTransport(FakeHttpClient $http): MicroBgTransport
{
    $factory = FakeHttpClient::factory();

    return new MicroBgTransport(new MicroBgConfig('api-id', 'secret'), $http, $factory, $factory);
}

it('posts a signed form body to the entry point', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    fakeMicroBgTransport($http)->call('getPartners', ['limit' => 50]);

    $request = $http->lastRequest();
    parse_str((string) $request->getBody(), $fields);

    expect((string) $request->getUri())->toBe('https://micro.bg/ExtApps/ExternalApp/API/')
        ->and($request->getMethod())->toBe('POST')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and($fields['ApiId'])->toBe('api-id')
        ->and(json_decode(base64_decode(urldecode(substr($fields['Request'], 0, -64))), true))
        ->toBe(['functionName' => 'getPartners', 'parameters' => ['limit' => 50], 'functionData' => null]);
});

it('drops null parameters', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    fakeMicroBgTransport($http)->call('getItems', ['limit' => 5, 'Code' => null]);

    parse_str((string) $http->lastRequest()->getBody(), $fields);

    expect(json_decode(base64_decode(urldecode(substr($fields['Request'], 0, -64))), true)['parameters'])
        ->toBe(['limit' => 5]);
});

it('sends functionData as a json object, never as an empty list', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    fakeMicroBgTransport($http)->call('insertPartner', [], []);

    parse_str((string) $http->lastRequest()->getBody(), $fields);

    expect(base64_decode(urldecode(substr($fields['Request'], 0, -64))))->toContain('"functionData":{}');
});

it('unwraps the envelope and returns the data node', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['id' => 8681]]]);

    expect(fakeMicroBgTransport($http)->call('getItems'))->toBe([['id' => 8681]]);
});

it('maps a failed envelope to an ApiException', function () {
    $http = FakeHttpClient::withJson(['status' => 0, 'errors' => ['nope'], 'data' => null]);

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))->toThrow(ApiException::class, 'nope');
});

it('maps a PSR-18 failure to a TransportException', function () {
    $http = FakeHttpClient::throwing(new FakeClientException('offline'));

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))
        ->toThrow(TransportException::class, 'HTTP transport error: offline');
});

it('rejects a non-2xx status before looking at the body', function () {
    $http = FakeHttpClient::withJson(['status' => 1], 503);

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))
        ->toThrow(ApiException::class, 'micro.bg returned HTTP 503');
});

it('rejects malformed json', function () {
    $http = FakeHttpClient::withRaw('{not json', 200);

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))
        ->toThrow(InvalidResponseException::class, 'Malformed JSON response');
});

it('rejects a body that is not a json object', function () {
    $http = FakeHttpClient::withRaw('"plain string"', 200);

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))
        ->toThrow(InvalidResponseException::class, 'Expected a JSON object');
});

it('rejects a list where an envelope is expected', function () {
    $http = FakeHttpClient::withRaw('[1,2]', 200);

    expect(fn () => fakeMicroBgTransport($http)->call('getItems'))
        ->toThrow(InvalidResponseException::class, 'Expected a JSON object');
});

it('callList returns an empty list when the method reports no data', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => null]);

    $list = fakeMicroBgTransport($http)->callList('getPartners', [], PartnerResult::class);

    expect($list->all())->toBe([]);
});

it('callList rejects a data node that is not a list', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => ['Name' => 'ACME']]);

    expect(fn () => fakeMicroBgTransport($http)->callList('getPartners', [], PartnerResult::class))
        ->toThrow(InvalidResponseException::class, 'Expected a list of rows for getPartners');
});

it('callList rejects a scalar row inside the list', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [7]]);

    expect(fn () => fakeMicroBgTransport($http)->callList('getPartners', [], PartnerResult::class))
        ->toThrow(InvalidResponseException::class, 'Each collection entry must be an object');
});

it('callOne rejects a list where a single object is expected', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['id' => 1]]]);

    expect(fn () => fakeMicroBgTransport($http)->callOne('insertPartner', [], null, PartnerResult::class))
        ->toThrow(InvalidResponseException::class, 'Expected a single object for insertPartner');
});
