# Microinvest PHP SDK

> **Warning:** This is a developer testing version of the library -- use at your own risk.
>
> The micro.bg backend is written against the *Api_v1.4* PDF and has not yet been exercised against a live account.

Framework-agnostic PHP SDK for [Microinvest](https://microinvest.net), covering **both** of its APIs:

| Backend | API | Reached via |
|---------|-----|-------------|
| **Warehouse Pro** | REST | Microinvest Utility Center on port `8700`/`8701`, on premise |
| **micro.bg** | signed RPC | `https://micro.bg/ExtApps/ExternalApp/API/`, hosted |

The two share a domain model (they sit on the same schema) but nothing on the wire, so each has its own config, transport and resources. What both support - partners, items, groups, VAT groups, payment types, objects, quantities - is expressed as `Ux2Dev\Microinvest\Contracts\Client`, so nomenclature code can be written once and pointed at either.

Works with plain PHP or Laravel.

## Requirements

- PHP 8.3 or higher
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
use Ux2Dev\Microinvest\Microinvest;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

$config = new WarehouseProConfig(
    baseUrl: 'http://127.0.0.1:8700', // Utility Center host; trailing slash optional
    apiKey:  'your-custom-token',      // optional; omit or pass null for anonymous access
);

$factory = new HttpFactory();
$microinvest = Microinvest::warehousePro($config, new Client(), $factory, $factory);

$items = $microinvest->items()->list(name: 'Cola*', pageSize: 200);

foreach ($items as $item) {
    echo $item->name . PHP_EOL;
}
```

### Plain PHP, micro.bg

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;
use Ux2Dev\Microinvest\Microinvest;

$config = new MicroBgConfig(
    apiId:     '1821761530712553',  // "API Идентификатор" from micro.bg
    secretKey: 'a6808173988b...',   // "Секретен ключ"; signs the payload, never sent
);

$factory = new HttpFactory();
$microbg = Microinvest::microBg($config, new Client(), $factory, $factory);

foreach ($microbg->items()->each() as $item) {
    echo $item->name, ' ', $item->priceOut2, PHP_EOL;
}
```

Register the application first: sign in as owner, **Администриране → Връзка с ел. магазини → Регистриране на ново приложение**, type **Външни приложения**, status active. The **Настройки** link on that row reveals the two credentials and lets you pick the user whose permissions the API calls run as.

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

### WarehouseProConfig

Every Warehouse Pro client takes a `WarehouseProConfig`. It is a `final readonly` value object: all inputs are validated at construction, the API key is private and redacted from `var_dump()`, and serialization is blocked.

```php
$config = new WarehouseProConfig(
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
            'driver'   => 'warehouse_pro',
            'base_url' => env('MICROINVEST_BASE_URL', 'http://127.0.0.1:8700'),
            'api_key'  => env('MICROINVEST_API_KEY'),
            'timeout'  => (int) env('MICROINVEST_TIMEOUT', 30),
        ],
        'online' => [
            'driver'      => 'micro_bg',
            'api_id'      => env('MICROBG_API_ID'),
            'secret_key'  => env('MICROBG_SECRET_KEY'),
            'entry_point' => env('MICROBG_ENTRY_POINT', 'https://micro.bg/ExtApps/ExternalApp/API/'),
            'timeout'     => (int) env('MICROBG_TIMEOUT', 30),
        ],
    ],
];
```

Each connection declares a `driver`: `warehouse_pro` or `micro_bg`. `MicroinvestManager::client()` returns `Contracts\Client`; type-hint the concrete client when you need a backend's extras.

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
| Config | `Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig` | Base URL + optional API key, validated |
| Config | `Ux2Dev\Microinvest\MicroBg\MicroBgConfig` | API id + secret key + entry point, validated |
| Transport | `Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport` | PSR-18 dispatch, query/body building, error mapping |
| Transport | `Ux2Dev\Microinvest\MicroBg\MicroBgTransport` | Signed RPC dispatch over one entry point |
| Signing | `Ux2Dev\Microinvest\MicroBg\RequestSigner` | json → base64 → urlencode → HMAC-SHA256 |
| Envelope | `Ux2Dev\Microinvest\MicroBg\Envelope` | Reads `{status\|success, errors[], data}` |
| Input DTOs | `Ux2Dev\Microinvest\Dto\Input\{Group}\{Entity}Input` | Mutation bodies; `toWarehouseProArray()` emits snake_case wire keys |
| Result DTOs | `Ux2Dev\Microinvest\Dto\Result\{Group}\{Entity}Result` | Typed rows; `fromWarehousePro()` hydrates responses |
| Resources | `Ux2Dev\Microinvest\{WarehousePro,MicroBg}\Resources\{Group}` | One method per API action |
| Root clients | `WarehousePro\WarehouseProClient`, `MicroBg\MicroBgClient` | Aggregators exposing every resource |
| Contracts | `Ux2Dev\Microinvest\Contracts\*` | What every Microinvest backend supports |
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

## Iterating everything

`partners()` and `items()` implement `Ux2Dev\Microinvest\Contracts\PartnerRepository` /
`ItemRepository`, whose `each()` walks the whole nomenclature one page at a time:

```php
foreach ($microinvest->partners()->each() as $partner) {
    echo $partner->company, PHP_EOL;
}
```

It is a generator - rows are fetched lazily, so memory stays flat regardless of how
many pages the server reports.

`Ux2Dev\Microinvest\Contracts\Client` is the interface every Microinvest backend
satisfies. Type-hint it when your code only needs the nomenclature; type-hint a
concrete client when you need that backend's extras.

### Which backend supports what

| | Warehouse Pro | micro.bg |
|---|---|---|
| `partners()` / `items()` - `get`, `create`, `update`, `each` | yes | yes |
| `listItemGroups`, `listPartnerGroups`, `listTaxGroups`, `listPaymentTypes`, `listObjects`, `listQuantities` | yes | yes |
| `partners()->delete()`, `items()->delete()` | - | yes |
| `groups()->create/rename/delete` | - | yes |
| `items()->prices()`, additional item codes | - | yes |
| `users()`, `documents()`, `payments()->list/get/create` | yes | - |
| `invoices()->create()` (returns a PDF url) | - | yes |
| `company()->get()`, `company()->bankAccounts()` | - | yes |
| `operations()->allocateCost()` | - | yes |
| Operations | flat row per line | document with nested lines |

Anything in a `-` cell simply does not exist on that client, so reaching for it is
a `TypeError` at the call site rather than a runtime surprise.

Operations are the one place the two backends model the same thing differently, which
is why they are absent from the contract: Warehouse Pro returns one flat `OperationResult`
per item line, while micro.bg returns an `OperationDocumentResult` header carrying
`OperationLineResult` children. Code that pushes sales has to pick a backend.

### Idempotent writes on micro.bg

micro.bg lets an integration stamp its own id on an operation and then work by that id,
which makes re-running a sync safe:

```php
use Ux2Dev\Microinvest\Dto\Input\Operations\OperationDocumentInput;
use Ux2Dev\Microinvest\Dto\Input\Operations\OperationLineInput;

$microbg->operations()->save(
    new OperationDocumentInput(
        operationType: 2,          // sale
        objectId: 6,
        lines: [new OperationLineInput(itemId: 8814, qtty: 1.0, price: 12.0)],
        partnerId: 2,
        extAppDocId: $order->id,   // your own order id
    ),
    byExtAppDocId: true,           // create on the first call, update afterwards
);
```

## Exceptions

All SDK exceptions extend `Ux2Dev\Microinvest\Exception\MicroinvestException`:

| Exception | When it is thrown |
|-----------|-------------------|
| `ConfigurationException` | Invalid `WarehouseProConfig` input, unknown connection or driver |
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
