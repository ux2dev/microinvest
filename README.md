# Microinvest Warehouse Pro PHP SDK

> **Warning:** This is a developer testing version of the library -- use at your own risk.

Framework-agnostic PHP SDK for the [Microinvest](https://microinvest.net) Warehouse Pro REST API (exposed by the Microinvest Utility Center on port `8700`/`8701`). Covers Items, Partners, Users, Locations, Operations, Store, Payments, Documents and VAT groups as resource methods with named arguments or typed input DTOs, returning typed result DTOs. Works with plain PHP or Laravel.

## Requirements

- PHP 8.2 or higher
- JSON extension
- A PSR-18 HTTP client and PSR-17 request/stream factories (Guzzle provides both)

## Installation

```bash
composer require ux2dev/microinvest
```

## Enabling the API

1. Open port `8700` in the firewall of the machine running Microinvest Utility Center.
2. In Microinvest Utility Center pick **Microinvest API** from the module list.
3. (Optional) Generate an API key in the settings and pass it as `apiKey`. If you skip this, the API is reachable anonymously.

## Quick Start

### Plain PHP

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Microinvest\Config\MicroinvestConfig;
use Ux2Dev\Microinvest\Microinvest;

$config = new MicroinvestConfig(
    baseUrl: 'http://127.0.0.1:8700', // Utility Center host; trailing slash optional
    apiKey:  'your-custom-token',      // optional; omit or pass null for anonymous access
);

$factory = new HttpFactory();
$microinvest = new Microinvest($config, new Client(), $factory, $factory);

$items = $microinvest->items()->list(name: 'Cola*', pageSize: 200);

foreach ($items as $item) {
    echo $item->name . PHP_EOL;
}
```

### Laravel

```php
use Ux2Dev\Microinvest\Dto\Input\Operations\OperationInput;
use Ux2Dev\Microinvest\Laravel\Facades\Microinvest;

$sale = Microinvest::operations()->create([
    new OperationInput(
        operationType: 2, // Sale
        goodId: '66',
        partnerId: 94,
        objectId: 2,
        qtty: 1.0,
        priceOut: 7.29,
        date: '2023-04-07',
        userId: 2,
    ),
]);

$documentNumber = $sale->first()->documentNumber;
```

## Configuration

### MicroinvestConfig

Every client takes a `MicroinvestConfig`. It is a `final readonly` value object: all inputs are validated at construction, the API key is private and redacted from `var_dump()`, and serialization is blocked.

```php
$config = new MicroinvestConfig(
    baseUrl: 'http://127.0.0.1:8700', // required
    apiKey:  'your-custom-token',      // optional; null = anonymous
    timeout: 30,                       // optional; seconds, default 30
);
```

Each Microinvest install is its own on-premise host, so `baseUrl` is per-machine. The API key (when set) is sent as the `X-API-Key` header on every request.

### Laravel configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=microinvest-config
```

This creates `config/microinvest.php`:

```php
return [
    'default' => env('MICROINVEST_CONNECTION', 'local'),
    'connections' => [
        'local' => [
            'base_url' => env('MICROINVEST_BASE_URL', 'http://127.0.0.1:8700'),
            'api_key'  => env('MICROINVEST_API_KEY'),
            'timeout'  => (int) env('MICROINVEST_TIMEOUT', 30),
        ],
    ],
];
```

### Multiple connections

Add more entries under `connections` and switch at runtime:

```php
use Ux2Dev\Microinvest\Laravel\MicroinvestManager;

$stock = app(MicroinvestManager::class)
    ->connection('warehouse-2')
    ->store()
    ->list(objectId: 4);
```

`connection()` returns an immutable clone; the default connection stays untouched.

## How the SDK is organised

| Layer | Location | Purpose |
|-------|----------|---------|
| Config | `Ux2Dev\Microinvest\Config\MicroinvestConfig` | Base URL + optional API key, validated |
| Transport | `Ux2Dev\Microinvest\Http\MicroinvestTransport` | PSR-18 dispatch, query/body building, error mapping |
| Input DTOs | `Ux2Dev\Microinvest\Dto\Input\{Group}\{Entity}Input` | Mutation bodies; `toArray()` emits snake_case wire keys |
| Result DTOs | `Ux2Dev\Microinvest\Dto\Result\{Group}\{Entity}Result` | Typed rows; `fromArray()` hydrates responses |
| Resources | `Ux2Dev\Microinvest\Resources\{Group}` | One method per API action |
| Root client | `Ux2Dev\Microinvest\Microinvest` | Aggregator exposing every resource |
| Laravel | `Ux2Dev\Microinvest\Laravel\*` | Service provider + multi-connection manager + facade |

Resource methods come in three shapes:

- **List** operations (`$microinvest->items()->list(...)`) take named argument filters and return a `ResultList<T>` of typed result DTOs.
- **Single-record** operations (`$microinvest->items()->get(2518)`) return the typed result DTO directly.
- **Mutations** (`$microinvest->items()->create(...)`) take an input DTO (or a list of them for operations) and return the created record(s).

## Resources

| Resource | Methods |
|----------|---------|
| `items()` | `list`, `get`, `create`, `update`, `groups` |
| `partners()` | `list`, `get`, `create`, `update`, `groups` |
| `users()` | `list`, `groups` |
| `locations()` | `list`, `groups` |
| `operations()` | `list`, `get`, `create` |
| `store()` | `list` |
| `payments()` | `list`, `get`, `create`, `types` |
| `documents()` | `list`, `get`, `create` |
| `vatGroups()` | `list` |

## Filtering & pagination

Collections accept exact-value filters as named arguments. A `*` in a string value triggers a LIKE search (e.g. `name: 'Cola*'`). Paging uses `page` / `pageSize` arguments (default page size 500); the response's `X-CurrentPage` / `X-TotalPages` headers are surfaced on the returned list.

Every `list()` method also takes a trailing `filters:` array as an escape hatch for any wire field the named arguments don't cover (the Microinvest API can filter "quite all collections by the exact value name"). Keys are raw snake_case wire names, and they override a colliding named argument:

```php
$microinvest->items()->list(name: 'Cola*', filters: ['barcode2' => '3800', 'catalog1' => 'A1']);
```

Response paging is surfaced on the returned list: 

```php
$list = $microinvest->operations()->list(operationType: 2, dateFrom: '2023-04-01', dateTo: '2023-04-19');

$list->all();          // list<OperationResult>
$list->first();        // OperationResult|null
$list->currentPage;    // int|null (from X-CurrentPage)
$list->totalPages;     // int|null (from X-TotalPages)
count($list);          // same as $list->count()

foreach ($list as $row) { /* ... */ }
```

## Exceptions

All SDK exceptions extend `Ux2Dev\Microinvest\Exception\MicroinvestException`:

| Exception | When it is thrown |
|-----------|-------------------|
| `ConfigurationException` | Invalid `MicroinvestConfig` input, unknown connection |
| `TransportException`     | PSR-18 client failure (network error, timeout) |
| `InvalidResponseException` | Empty body, malformed JSON, unexpected shape |
| `ApiException` | HTTP non-2xx. Carries `httpStatus`, `apiCode`, `apiMessage`, and the decoded `body`. |

## Testing

```bash
composer install
vendor/bin/pest
XDEBUG_MODE=coverage vendor/bin/pest --coverage
```

The suite mocks a PSR-18 client to exercise every resource method end-to-end.

## License

MIT
