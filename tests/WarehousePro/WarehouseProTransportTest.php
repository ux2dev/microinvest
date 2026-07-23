<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;
use Ux2Dev\Microinvest\Tests\Http\FakeClientException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('builds the url from base url, path and filtered query', function () {
    $http = FakeHttpClient::withJson([]);
    $transport = fakeWarehousePro($http)->transport;

    $transport->request('GET', '/Items', ['name' => 'Cola*', 'code' => null, 'page' => 1]);

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toBe('http://127.0.0.1:8700/Items?name=Cola%2A&page=1');
});

it('omits the query string when no filters are set', function () {
    $http = FakeHttpClient::withJson([]);
    $transport = fakeWarehousePro($http)->transport;

    $transport->request('GET', '/VATGroups', ['page' => null]);

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/VATGroups');
});

it('sends the X-API-Key header when a key is configured', function () {
    $http = FakeHttpClient::withJson([]);
    $transport = fakeWarehousePro($http, apiKey: 'my-token')->transport;

    $transport->request('GET', '/Items');

    expect($http->lastRequest()->getHeaderLine('X-API-Key'))->toBe('my-token')
        ->and($http->lastRequest()->getHeaderLine('Accept'))->toBe('application/json');
});

it('omits the X-API-Key header for anonymous access', function () {
    $http = FakeHttpClient::withJson([]);
    $transport = fakeWarehousePro($http, apiKey: null)->transport;

    $transport->request('GET', '/Items');

    expect($http->lastRequest()->hasHeader('X-API-Key'))->toBeFalse();
});

it('encodes a JSON body and sets the content type for writes', function () {
    $http = FakeHttpClient::withJson([]);
    $transport = fakeWarehousePro($http)->transport;

    $transport->request('POST', '/Item', [], ['name' => 'Cola', 'price_in' => 1.5]);

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('POST')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json; charset=utf-8')
        ->and((string) $request->getBody())->toBe('{"name":"Cola","price_in":1.5}');
});

it('reads the paging headers into the envelope', function () {
    $http = FakeHttpClient::withJson([], headers: ['X-CurrentPage' => '3', 'X-TotalPages' => '42']);
    $transport = fakeWarehousePro($http)->transport;

    $env = $transport->request('GET', '/Items');

    expect($env['currentPage'])->toBe(3)
        ->and($env['totalPages'])->toBe(42);
});

it('leaves paging metadata null when headers are absent', function () {
    $env = fakeWarehousePro(FakeHttpClient::withJson([]))->transport->request('GET', '/Items');

    expect($env['currentPage'])->toBeNull()
        ->and($env['totalPages'])->toBeNull();
});

it('wraps PSR-18 client failures in a TransportException', function () {
    $http = FakeHttpClient::throwing(new FakeClientException('connection refused'));
    $transport = fakeWarehousePro($http)->transport;

    $transport->request('GET', '/Items');
})->throws(TransportException::class, 'HTTP transport error');

it('throws when the request body cannot be encoded', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withJson([]))->transport;

    $transport->request('POST', '/Item', [], ['bad' => NAN]);
})->throws(InvalidResponseException::class, 'Failed to encode request body');

it('throws on malformed JSON', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withRaw('{not json'))->transport;

    $transport->request('GET', '/Items');
})->throws(InvalidResponseException::class, 'Malformed JSON');

it('throws when a success body is not an array or object', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withRaw('"hello"'))->transport;

    $transport->request('GET', '/Items');
})->throws(InvalidResponseException::class, 'Expected a JSON array or object');

it('throws on an empty success body', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withRaw(''))->transport;

    $transport->request('GET', '/Items');
})->throws(InvalidResponseException::class, 'Expected a JSON array or object');

it('maps a wrapped error envelope to an ApiException', function () {
    $http = FakeHttpClient::withJson(['error' => ['code' => 5, 'message' => 'Value not found: id.']], status: 404);
    $transport = fakeWarehousePro($http)->transport;

    try {
        $transport->request('GET', '/Item', ['id' => 999]);
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect($e->httpStatus)->toBe(404)
            ->and($e->apiCode)->toBe(5)
            ->and($e->apiMessage)->toBe('Value not found: id.')
            ->and($e->getMessage())->toBe('Value not found: id.')
            ->and($e->body)->toBe(['error' => ['code' => 5, 'message' => 'Value not found: id.']]);
    }
});

it('maps a bare error body to an ApiException', function () {
    $http = FakeHttpClient::withJson(['code' => 3, 'message' => 'Invalid value: operation_type.'], status: 400);
    $transport = fakeWarehousePro($http)->transport;

    try {
        $transport->request('GET', '/Operations');
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect($e->httpStatus)->toBe(400)
            ->and($e->apiCode)->toBe(3)
            ->and($e->apiMessage)->toBe('Invalid value: operation_type.');
    }
});

it('falls back to a generic message for an error without a body', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withRaw('', status: 500))->transport;

    try {
        $transport->request('GET', '/Items');
        $this->fail('Expected ApiException');
    } catch (ApiException $e) {
        expect($e->httpStatus)->toBe(500)
            ->and($e->apiCode)->toBeNull()
            ->and($e->getMessage())->toBe('Microinvest API returned HTTP 500');
    }
});

it('hydrates a list response via requestList', function () {
    $http = FakeHttpClient::withJson(
        [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']],
        headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '1'],
    );
    $list = fakeWarehousePro($http)->transport->requestList('GET', '/Items', [], ItemResult::class);

    expect($list->count())->toBe(2)
        ->and($list->first())->toBeInstanceOf(ItemResult::class)
        ->and($list->first()->name)->toBe('A');
});

it('rejects an object where a list is expected', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withJson(['id' => 1]))->transport;

    $transport->requestList('GET', '/Items', [], ItemResult::class);
})->throws(InvalidResponseException::class, 'Expected a JSON array');

it('rejects a scalar row inside a list', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withJson([1, 2]))->transport;

    $transport->requestList('GET', '/Items', [], ItemResult::class);
})->throws(InvalidResponseException::class, 'must be an object');

it('hydrates a single object via requestOne', function () {
    $item = fakeWarehousePro(FakeHttpClient::withJson(['id' => 7, 'name' => 'Solo']))
        ->transport->requestOne('GET', '/Item', ['id' => 7], null, ItemResult::class);

    expect($item)->toBeInstanceOf(ItemResult::class)
        ->and($item->id)->toBe(7);
});

it('rejects an array where a single object is expected', function () {
    $transport = fakeWarehousePro(FakeHttpClient::withJson([['id' => 1]]))->transport;

    $transport->requestOne('GET', '/Item', [], null, ItemResult::class);
})->throws(InvalidResponseException::class, 'got an array');
