# Фаза 2: micro.bg backend

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавя `src/MicroBg/` — подписан RPC транспорт срещу micro.bg External App API — и разширява споделените DTO-та с micro.bg диалекта, така че `MicroBgClient implements Contracts\Client`.

**Architecture:** `MicroBgConfig` (apiId + secretKey) → `RequestSigner` (json→base64→urlencode→HMAC) → `MicroBgTransport::call()` → `Envelope::unwrap()` → същите `Dto/Result/*` класове, хидратирани през `fromMicroBg()`. Ресурсите са един клас на група методи, както при Warehouse Pro. Двата backend-а не споделят нито ред транспортен код — споделят DTO-та, `ResultList`, изключения и контракти.

**Tech Stack:** PHP 8.2+, PSR-18 + PSR-17 (инжектирани), Pest 4, PCOV в CI.

## Global Constraints

- PHP `>=8.2`; всеки файл започва с `declare(strict_types=1);`
- Всички конкретни класове са `final`; Input DTO-тата остават `readonly`
- PSR-18/PSR-17 се инжектират — никакъв discovery
- Никакви нови зависимости в `require`
- Покритието трябва да остане 100% (`composer test:coverage` в CI); локално не се мери — няма Xdebug/PCOV
- Работи се в клон `feat/micro-bg-transport`
- **Пише се срещу `Api_v1.4.pdf`, без реален акаунт.** Всяко място, където PDF-ът е двусмислен, се имплементира защитно и се коментира в кода с `// PDF v1.4: ...`

**Референтна спецификация:** `docs/superpowers/specs/2026-07-23-two-backend-sdk-design.md`

## Протоколът в едно изречение

`POST https://micro.bg/ExtApps/ExternalApp/API/` с form-encoded тяло от точно две полета: `ApiId` и `Request`, където `Request = urlencode(base64_encode(json_encode({functionName, parameters, functionData}))) . hash_hmac('sha256', <същият низ>, $secretKey)`.

## Файлова структура

| Път | Отговорност |
|---|---|
| `src/MicroBg/MicroBgConfig.php` | apiId + secretKey + entryPoint, валидирани, secretKey редактиран |
| `src/MicroBg/RequestSigner.php` | превръща payload в двойката `{ApiId, Request}` |
| `src/MicroBg/Envelope.php` | разчита `{status\|success, errors[], data}` |
| `src/MicroBg/MicroBgTransport.php` | HTTP диспечър + хидратиращи помощници |
| `src/MicroBg/MicroBgClient.php` | lazy ресурси, `implements Contracts\Client` |
| `src/MicroBg/Resources/*.php` | Resource, Partners, Items, Groups, TaxGroups, Payments, Objects |
| `src/Contracts/Dto/FromMicroBg.php`, `ToMicroBg.php` | маркери за втория диалект |
| `src/Dto/Result/Items/ItemAddCodeResult.php` | нов вложен DTO за `AddCodes[]` |

---

### Task 1: `MicroBgConfig`

**Files:**
- Create: `src/MicroBg/MicroBgConfig.php`
- Test: `tests/MicroBg/MicroBgConfigTest.php`

**Interfaces:**
- Consumes: `Ux2Dev\Microinvest\Exception\ConfigurationException`
- Produces: `MicroBgConfig::__construct(string $apiId, string $secretKey, string $entryPoint = 'https://micro.bg/ExtApps/ExternalApp/API/', int $timeout = 30)`; public readonly `$apiId`, `$entryPoint`, `$timeout`; `getSecretKey(): string`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;

it('defaults to the documented entry point and normalises the trailing slash', function () {
    expect((new MicroBgConfig('1821761530712553', 'a6808173988b'))->entryPoint)
        ->toBe('https://micro.bg/ExtApps/ExternalApp/API/')
        ->and((new MicroBgConfig('a', 'b', 'https://staging.micro.bg/api'))->entryPoint)
        ->toBe('https://staging.micro.bg/api/');
});

it('exposes the api id but hides the secret key', function () {
    $config = new MicroBgConfig('1821761530712553', 'a6808173988b');

    expect($config->apiId)->toBe('1821761530712553')
        ->and($config->getSecretKey())->toBe('a6808173988b')
        ->and($config->__debugInfo()['secretKey'])->toBe('[REDACTED]')
        ->and($config->__debugInfo()['apiId'])->toBe('1821761530712553');
});

it('rejects invalid input', function (string $apiId, string $secret, string $entry, int $timeout, string $message) {
    expect(fn () => new MicroBgConfig($apiId, $secret, $entry, $timeout))
        ->toThrow(ConfigurationException::class, $message);
})->with([
    ['', 'k', 'https://micro.bg/', 30, 'apiId must not be empty'],
    ['a', '', 'https://micro.bg/', 30, 'secretKey must not be empty'],
    ['a', 'k', 'micro.bg', 30, 'entryPoint must start with http:// or https://'],
    ['a', 'k', 'https://micro.bg/', 0, 'timeout must be at least 1 second'],
]);

it('refuses to be serialized', function () {
    expect(fn () => serialize(new MicroBgConfig('a', 'k')))->toThrow(LogicException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/MicroBgConfigTest.php`
Expected: FAIL — `Class "Ux2Dev\Microinvest\MicroBg\MicroBgConfig" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use Ux2Dev\Microinvest\Exception\ConfigurationException;

/**
 * Validated connection details for one micro.bg external application.
 *
 * Both credentials come from micro.bg: Администриране → Връзка с ел. магазини →
 * Регистриране на ново приложение → Настройки. The secret key never travels on
 * the wire; it only signs the payload.
 */
final readonly class MicroBgConfig
{
    public const DEFAULT_ENTRY_POINT = 'https://micro.bg/ExtApps/ExternalApp/API/';

    public string $entryPoint;

    public function __construct(
        public string $apiId,
        private string $secretKey,
        string $entryPoint = self::DEFAULT_ENTRY_POINT,
        public int $timeout = 30,
    ) {
        if ($apiId === '') {
            throw new ConfigurationException('apiId must not be empty');
        }

        if ($secretKey === '') {
            throw new ConfigurationException('secretKey must not be empty');
        }

        if (! preg_match('~^https?://~i', $entryPoint)) {
            throw new ConfigurationException('entryPoint must start with http:// or https://');
        }

        if ($timeout < 1) {
            throw new ConfigurationException('timeout must be at least 1 second');
        }

        $this->entryPoint = rtrim($entryPoint, '/') . '/';
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'apiId' => $this->apiId,
            'secretKey' => '[REDACTED]',
            'entryPoint' => $this->entryPoint,
            'timeout' => $this->timeout,
        ];
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        throw new \LogicException('MicroBgConfig must not be serialized as it contains a secret key');
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new \LogicException('MicroBgConfig must not be unserialized');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/MicroBg/MicroBgConfigTest.php`
Expected: PASS (7 tests — 2 + 4 dataset rows + 1)

- [ ] **Step 5: Commit**

```bash
git add src/MicroBg/MicroBgConfig.php tests/MicroBg/MicroBgConfigTest.php
git commit -m "feat: add MicroBgConfig"
```

---

### Task 2: `RequestSigner`

Най-рисковото място в целия backend. Тества се без HTTP.

**Files:**
- Create: `src/MicroBg/RequestSigner.php`
- Test: `tests/MicroBg/RequestSignerTest.php`

**Interfaces:**
- Consumes: `Ux2Dev\Microinvest\Exception\InvalidResponseException`
- Produces: `RequestSigner::__construct(string $apiId, string $secretKey)`; `sign(array $payload): array{ApiId: string, Request: string}`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/RequestSignerTest.php`
Expected: FAIL — `Class "Ux2Dev\Microinvest\MicroBg\RequestSigner" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use JsonException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;

/**
 * Turns a request payload into the two POST fields micro.bg expects.
 *
 * PDF v1.4 pins the order exactly: json_encode, then base64_encode, then
 * urlencode; the HMAC is taken over the *encoded* string, not over the JSON,
 * and is concatenated onto it. Reordering any step invalidates the signature.
 */
final readonly class RequestSigner
{
    private const HASH_ALGO = 'sha256';

    public function __construct(
        private string $apiId,
        private string $secretKey,
    ) {
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array{ApiId: string, Request: string}
     */
    public function sign(array $payload): array
    {
        try {
            // No JSON_UNESCAPED_UNICODE: the reference implementation uses bare
            // json_encode, and the hash must match what the server recomputes.
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $e) {
            throw new InvalidResponseException(
                'Failed to encode micro.bg request payload: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $encoded = urlencode(base64_encode($json));

        return [
            'ApiId' => $this->apiId,
            'Request' => $encoded . hash_hmac(self::HASH_ALGO, $encoded, $this->secretKey),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/MicroBg/RequestSignerTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add src/MicroBg/RequestSigner.php tests/MicroBg/RequestSignerTest.php
git commit -m "feat: add micro.bg request signer"
```

---

### Task 3: `Envelope`

**Files:**
- Create: `src/MicroBg/Envelope.php`
- Test: `tests/MicroBg/EnvelopeTest.php`

**Interfaces:**
- Consumes: `ApiException`, `InvalidResponseException`
- Produces: `Envelope::unwrap(array $decoded, string $functionName): mixed`

- [ ] **Step 1: Write the failing test**

```php
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
            ->and($e->body['errors'])->toBe(['Invalid code', 'Partner not found']);
    }
});

it('names the function when the failure carries no errors', function () {
    expect(fn () => Envelope::unwrap(['status' => false, 'errors' => []], 'saveOperation'))
        ->toThrow(ApiException::class, 'micro.bg rejected saveOperation');
});

it('stringifies non-string error entries', function () {
    expect(fn () => Envelope::unwrap(['status' => 0, 'errors' => [['code' => 7]]], 'getItems'))
        ->toThrow(ApiException::class, '{"code":7}');
});

it('rejects an envelope with neither flag', function () {
    expect(fn () => Envelope::unwrap(['data' => []], 'getItems'))
        ->toThrow(InvalidResponseException::class, 'neither a status nor a success flag');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/EnvelopeTest.php`
Expected: FAIL — `Class "Ux2Dev\Microinvest\MicroBg\Envelope" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;

/**
 * Reads the envelope every micro.bg method returns.
 *
 * PDF v1.4 is inconsistent here: the class_ApiMicroBg description promises a
 * `success` property while every worked example returns `status`. Both are
 * accepted. The HTTP status is always 200, so failure only shows up in here.
 */
final class Envelope
{
    /**
     * @param  array<string, mixed> $decoded
     * @return mixed the `data` node, or null when the method reports none
     */
    public static function unwrap(array $decoded, string $functionName): mixed
    {
        $ok = $decoded['status'] ?? $decoded['success'] ?? null;

        if ($ok === null) {
            throw new InvalidResponseException(
                "micro.bg response for {$functionName} has neither a status nor a success flag",
            );
        }

        if (! $ok) {
            $message = self::errorMessage($decoded);

            throw new ApiException(
                $message ?? "micro.bg rejected {$functionName}",
                httpStatus: 200,
                apiMessage: $message,
                body: $decoded,
            );
        }

        return $decoded['data'] ?? null;
    }

    /** @param array<string, mixed> $decoded */
    private static function errorMessage(array $decoded): ?string
    {
        $errors = $decoded['errors'] ?? null;

        if (is_string($errors)) {
            return $errors === '' ? null : $errors;
        }

        if (! is_array($errors) || $errors === []) {
            return null;
        }

        $parts = array_map(
            static fn (mixed $e): string => is_string($e) ? $e : (string) json_encode($e, JSON_UNESCAPED_UNICODE),
            $errors,
        );

        $message = implode('; ', array_filter($parts, static fn (string $p): bool => $p !== ''));

        return $message === '' ? null : $message;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/MicroBg/EnvelopeTest.php`
Expected: PASS (8 tests — 3 dataset rows + 5)

- [ ] **Step 5: Commit**

```bash
git add src/MicroBg/Envelope.php tests/MicroBg/EnvelopeTest.php
git commit -m "feat: add micro.bg response envelope reader"
```

---

### Task 4: `MicroBgTransport`

**Files:**
- Create: `src/MicroBg/MicroBgTransport.php`
- Test: `tests/MicroBg/MicroBgTransportTest.php`
- Modify: `tests/Pest.php` (нов helper)

**Interfaces:**
- Consumes: `MicroBgConfig`, `RequestSigner`, `Envelope`, `ResultList`, `FromMicroBg` (Task 5)
- Produces:
  - `MicroBgTransport::__construct(MicroBgConfig $config, ClientInterface $httpClient, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)`; public readonly `$config`
  - `call(string $functionName, array $parameters = [], ?array $functionData = null): mixed`
  - `callList(string $functionName, array $parameters, string $resultClass, ?array $functionData = null): ResultList`
  - `callOne(string $functionName, array $parameters, ?array $functionData, string $resultClass): object`
  - test helper `fakeMicroBg(FakeHttpClient $http, string $apiId = 'api-id', string $secretKey = 'secret'): MicroBgClient` (добавя се в Task 8; в този task тестовете инстанцират транспорта директно)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/MicroBgTransportTest.php`
Expected: FAIL — `Class "Ux2Dev\Microinvest\MicroBg\MicroBgTransport" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * Low-level dispatcher for the micro.bg External App API. Every method is a
 * POST to the same entry point; the method name travels inside the signed
 * payload rather than in the URL.
 */
final class MicroBgTransport
{
    private readonly RequestSigner $signer;

    public function __construct(
        public readonly MicroBgConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->signer = new RequestSigner($config->apiId, $config->getSecretKey());
    }

    /**
     * @param  array<string, mixed> $parameters  null values are dropped
     * @param  array<string, mixed>|null $functionData
     * @return mixed the envelope's `data` node
     */
    public function call(string $functionName, array $parameters = [], ?array $functionData = null): mixed
    {
        $fields = $this->signer->sign([
            'functionName' => $functionName,
            'parameters' => array_filter($parameters, static fn (mixed $v): bool => $v !== null),
            // Cast so an all-null DTO encodes as {} rather than [].
            'functionData' => $functionData === null ? null : (object) $functionData,
        ]);

        $request = $this->requestFactory->createRequest('POST', $this->config->entryPoint)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($fields)));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('HTTP transport error: ' . $e->getMessage(), previous: $e);
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new ApiException("micro.bg returned HTTP {$status}", httpStatus: $status);
        }

        try {
            $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseException(
                "Malformed JSON response for {$functionName}: " . $e->getMessage(),
                previous: $e,
            );
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidResponseException("Expected a JSON object for {$functionName}");
        }

        return Envelope::unwrap($decoded, $functionName);
    }

    /**
     * @template T of object
     * @param  array<string, mixed> $parameters
     * @param  class-string<T&FromMicroBg> $resultClass
     * @param  array<string, mixed>|null $functionData
     * @return ResultList<T>
     */
    public function callList(
        string $functionName,
        array $parameters,
        string $resultClass,
        ?array $functionData = null,
    ): ResultList {
        $rows = $this->call($functionName, $parameters, $functionData);

        if ($rows === null) {
            return new ResultList([]);
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new InvalidResponseException("Expected a list of rows for {$functionName}");
        }

        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidResponseException('Each collection entry must be an object');
            }

            $items[] = $resultClass::fromMicroBg($row);
        }

        return new ResultList($items);
    }

    /**
     * @template T of object
     * @param  array<string, mixed> $parameters
     * @param  array<string, mixed>|null $functionData
     * @param  class-string<T&FromMicroBg> $resultClass
     * @return T
     */
    public function callOne(
        string $functionName,
        array $parameters,
        ?array $functionData,
        string $resultClass,
    ): object {
        $row = $this->call($functionName, $parameters, $functionData);

        if (! is_array($row) || array_is_list($row)) {
            throw new InvalidResponseException("Expected a single object for {$functionName}");
        }

        return $resultClass::fromMicroBg($row);
    }
}
```

- [ ] **Step 4: Run the transport test**

Run: `vendor/bin/pest tests/MicroBg/MicroBgTransportTest.php`
Expected: PASS (8 tests). The `use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;` line refers to an interface that only arrives in Task 5, but a `use` statement is not an autoload trigger — it is only resolved where the name is actually used, and here that is a docblock. Nothing to stub.

- [ ] **Step 5: Commit**

```bash
git add src/MicroBg/MicroBgTransport.php tests/MicroBg/MicroBgTransportTest.php
git commit -m "feat: add micro.bg transport"
```

---

### Task 5: micro.bg диалект върху споделените DTO-та

**Files:**
- Create: `src/Contracts/Dto/FromMicroBg.php`, `src/Contracts/Dto/ToMicroBg.php`
- Create: `src/Dto/Result/Items/ItemAddCodeResult.php`
- Modify: `src/Dto/Result/Partners/PartnerResult.php`, `Items/ItemResult.php`, `NomenclatureGroupResult.php`, `VatGroups/VatGroupResult.php`, `Payments/PaymentTypeResult.php`, `Locations/LocationResult.php`, `Store/StoreResult.php`
- Modify: `src/Dto/Input/Partners/PartnerInput.php`, `Items/ItemInput.php`
- Test: `tests/Dto/MicroBgDialectTest.php`

**Interfaces:**
- Produces:
  - `FromMicroBg::fromMicroBg(array $data): static`
  - `ToMicroBg::toMicroBgArray(): array`
  - `ItemAddCodeResult::__construct(?int $measureId, ?string $code, ?int $codeType, ?float $ratio)` + `fromMicroBg()`

**Нови полета по DTO** (всички `?T = null`, добавят се в края на конструктора, за да не се чупи позиционното извикване):

| DTO | Нови полета |
|---|---|
| `PartnerResult` | `contactPerson: ?string`, `partnerNote: ?string`, `groupName: ?string`, `groupPath: ?string`, `dateUpdated: ?string` |
| `ItemResult` | `notStorable: ?bool`, `taxValue: ?float`, `measureId: ?int`, `groupName: ?string`, `groupPath: ?string`, `warrantyMonths: ?int`, `warrantyDays: ?int`, `dateUpdated: ?string`, `addCodes: array` (default `[]`, `list<ItemAddCodeResult>`) |
| `NomenclatureGroupResult` | `path: ?string`, `parentId: ?int` |
| `PaymentTypeResult` | `fiscalMode: ?int`, `deleted: ?bool` |
| `LocationResult` | `address: ?string` |
| `VatGroupResult` | нищо |
| `StoreResult` | нищо |
| `PartnerInput` | `contactPerson: ?string`, `partnerNote: ?string` |
| `ItemInput` | `notStorable: ?bool`, `measureId: ?int`, `warrantyMonths: ?int`, `warrantyDays: ?int` |

**Мапинги `fromMicroBg()`** (ляво = wire ключ от PDF, дясно = свойство):

`PartnerResult` — `id→id`, `Code→code`, `Name→company`, `MOL→mol`, `City→city`, `Address→address`, `Phone→phone`, `eMail→email`, `TaxID→taxId`, `VatID→vatId`, `PriceGroup→priceGroup`, `Discount→discount`, `PartnerType→type`, `CardNumber→cardNumber`, `GroupId→groupId`, `Deleted→deleted`, `ContactPerson→contactPerson`, `PartnerNote→partnerNote`, `GroupName→groupName`, `GroupPath→groupPath`, `DateUpdated→dateUpdated`. Всички Warehouse-only полета остават `null`.

`ItemResult` — `id→id`, `Code→code`, `Name→name`, `Barcode→barcode1`, `GroupId→groupId`, `GroupName→groupName`, `GroupPath→groupPath`, `NotStorable→notStorable`, `TaxGroup→taxGroup`, `TaxValue→taxValue`, `MeasureName→measure1`, `MeasureId→measureId`, `PriceIn→priceIn`, `PriceOut1..PriceOut10→priceOut1..priceOut10`, `Deleted→deleted`, `Description→description`, `WarrantyMonths→warrantyMonths`, `WarrantyDays→warrantyDays`, `DateUpdated→dateUpdated`, `AddCodes[]→addCodes` (всеки ред през `ItemAddCodeResult::fromMicroBg()`).

`ItemAddCodeResult` — `MeasureId→measureId`, `Code→code`, `CodeType→codeType`, `Ratio→ratio`.

`NomenclatureGroupResult` — `id→id`, `Name→name`, `Path→path`, `parentId→parentId`. `code` остава `null`.

`VatGroupResult` — `TaxGroup→id`, `Name→name`, `TaxValue→vatValue`. `code` остава `null`.

`PaymentTypeResult` — `id→id`, `Name→name`, `PaymentMethod→paymentMethod`, `FiscalMode→fiscalMode`, `Deleted→deleted`.

`LocationResult` — `id→id`, `Name→name`, `Address→address`, `PriceGroup→priceGroup`, `Deleted→deleted`.

`StoreResult` — `ItemId→goodId` (като string), `Qtty→qtty`.

**Мапинги `toMicroBgArray()`** (само не-null свойства):

`PartnerInput` — `id→id` (малка буква, така е в PDF), `code→Code`, `company→Name`, `mol→MOL`, `city→City`, `address→Address`, `phone→Phone`, `email→eMail`, `taxId→TaxID`, `vatId→VatID`, `priceGroup→PriceGroup`, `discount→Discount`, `type→PartnerType`, `cardNumber→CardNumber`, `groupId→GroupId`, `deleted→Deleted`, `contactPerson→ContactPerson`, `partnerNote→PartnerNote`.

`ItemInput` — `id→id`, `code→Code`, `name→Name`, `barcode1→Barcode`, `notStorable→NotStorable`, `taxGroup→TaxGroup`, `groupId→GroupId`, `measureId→MeasureId`, `priceOut1..10→PriceOut1..10`, `deleted→Deleted`, `description→Description`, `warrantyMonths→WarrantyMonths`, `warrantyDays→WarrantyDays`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;
use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemAddCodeResult;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;

it('hydrates a partner from the micro.bg dialect', function () {
    // Verbatim from Api_v1.4.pdf, section 3.1.
    $p = PartnerResult::fromMicroBg([
        'id' => 17, 'Code' => 102, 'Name' => 'АКЦЕНТ-2006 - ЕООД', 'MOL' => 'Иван Стоименов Тошев',
        'City' => 'САНДАНСКИ', 'Address' => 'ул. СТАНКЕ ДИМИТРОВ №7', 'Phone' => '+359 883115782',
        'eMail' => '', 'TaxID' => '101744907', 'VatID' => 'BG101744907', 'PriceGroup' => 2,
        'CardNumber' => '9690017239039', 'Discount' => 0.0, 'PartnerType' => 1,
        'GroupName' => 'Складове', 'GroupPath' => '-1', 'ContactPerson' => '', 'PartnerNote' => '',
        'GroupId' => 38, 'Deleted' => 0, 'DateUpdated' => '2016-07-29 12:09:15',
    ]);

    expect($p)->toBeInstanceOf(FromMicroBg::class)
        ->and($p->id)->toBe(17)
        ->and($p->code)->toBe('102')
        ->and($p->company)->toBe('АКЦЕНТ-2006 - ЕООД')
        ->and($p->email)->toBe('')
        ->and($p->taxId)->toBe('101744907')
        ->and($p->type)->toBe(1)
        ->and($p->groupPath)->toBe('-1')
        ->and($p->dateUpdated)->toBe('2016-07-29 12:09:15')
        ->and($p->deleted)->toBeFalse()
        // Warehouse Pro only - absent from this dialect.
        ->and($p->bankName)->toBeNull()
        ->and($p->paymentDays)->toBeNull();
});

it('hydrates an item including its additional codes', function () {
    // Verbatim from Api_v1.4.pdf, section 4.1.
    $i = ItemResult::fromMicroBg([
        'id' => 2, 'GroupName' => 'Алкохол', 'GroupPath' => 'ААНААВ', 'GroupId' => 1726,
        'Code' => 56, 'Barcode' => '54491472', 'Name' => "Водка Мери Джейн's 0.5",
        'NotStorable' => 0, 'TaxGroup' => 1, 'TaxValue' => 20, 'MeasureName' => 'Бр.', 'MeasureId' => 6,
        'PriceIn' => 13.0195308, 'PriceOut1' => 19.00, 'PriceOut2' => 35.00, 'PriceOut10' => 0.0,
        'Deleted' => 0, 'Description' => '', 'WarrantyMonths' => 0, 'WarrantyDays' => 0,
        'DateUpdated' => '2018-06-28 11:08:54',
        'AddCodes' => [['MeasureId' => 108, 'Code' => 7777, 'CodeType' => 1, 'Ratio' => 6]],
    ]);

    expect($i->id)->toBe(2)
        ->and($i->barcode1)->toBe('54491472')
        ->and($i->measure1)->toBe('Бр.')
        ->and($i->measureId)->toBe(6)
        ->and($i->taxValue)->toBe(20.0)
        ->and($i->priceOut2)->toBe(35.0)
        ->and($i->notStorable)->toBeFalse()
        ->and($i->addCodes)->toHaveCount(1)
        ->and($i->addCodes[0])->toBeInstanceOf(ItemAddCodeResult::class)
        ->and($i->addCodes[0]->code)->toBe('7777')
        ->and($i->addCodes[0]->ratio)->toBe(6.0);
});

it('defaults addCodes to an empty list', function () {
    expect(ItemResult::fromMicroBg(['id' => 3])->addCodes)->toBe([]);
});

it('hydrates the lookup DTOs', function () {
    expect(NomenclatureGroupResult::fromMicroBg(['id' => 1733, 'Name' => 'Вина', 'Path' => 'ААА', 'parentId' => 0]))
        ->toMatchArray(['id' => 1733, 'name' => 'Вина', 'path' => 'ААА', 'parentId' => 0])
        ->and(VatGroupResult::fromMicroBg(['TaxGroup' => 2, 'Name' => 'ДДС(Г) 9%', 'TaxValue' => 9]))
        ->toMatchArray(['id' => 2, 'name' => 'ДДС(Г) 9%', 'vatValue' => 9.0])
        ->and(PaymentTypeResult::fromMicroBg(['id' => 3, 'Name' => 'С карта', 'PaymentMethod' => 3, 'FiscalMode' => 2, 'Deleted' => 0]))
        ->toMatchArray(['id' => 3, 'name' => 'С карта', 'paymentMethod' => 3, 'fiscalMode' => 2, 'deleted' => false])
        ->and(LocationResult::fromMicroBg(['id' => 2, 'Name' => 'Склад', 'Address' => 'София', 'PriceGroup' => 1, 'Deleted' => 0]))
        ->toMatchArray(['id' => 2, 'name' => 'Склад', 'address' => 'София', 'priceGroup' => 1])
        ->and(StoreResult::fromMicroBg(['ItemId' => 8681, 'Qtty' => -62.0]))
        ->toMatchArray(['goodId' => '8681', 'qtty' => -62.0]);
});

it('serialises a partner to the micro.bg dialect', function () {
    $input = new PartnerInput(
        company: 'МИКРОСИСТЕМИ ООД',
        city: 'СОФИЯ',
        taxId: '831826092',
        priceGroup: 2,
        type: 1,
        contactPerson: 'Иван',
    );

    expect($input)->toBeInstanceOf(ToMicroBg::class)
        ->and($input->toMicroBgArray())->toBe([
            'Name' => 'МИКРОСИСТЕМИ ООД',
            'City' => 'СОФИЯ',
            'TaxID' => '831826092',
            'PriceGroup' => 2,
            'PartnerType' => 1,
            'ContactPerson' => 'Иван',
        ]);
});

it('serialises an item to the micro.bg dialect', function () {
    $input = new ItemInput(
        name: 'формуляр Стокова разписка',
        code: '66880',
        barcode1: '5204533130138',
        taxGroup: 1,
        groupId: 38,
        priceOut1: 2.80,
        measureId: 1,
    );

    expect($input->toMicroBgArray())->toBe([
        'Code' => '66880',
        'Name' => 'формуляр Стокова разписка',
        'Barcode' => '5204533130138',
        'TaxGroup' => 1,
        'GroupId' => 38,
        'MeasureId' => 1,
        'PriceOut1' => 2.80,
    ]);
});

it('keeps the Warehouse Pro dialect working', function () {
    expect(PartnerResult::fromWarehousePro(['id' => 1, 'company' => 'X'])->company)->toBe('X')
        ->and((new PartnerInput(company: 'X'))->toWarehouseProArray())->toBe(['company' => 'X']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Dto/MicroBgDialectTest.php`
Expected: FAIL — `Interface "Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg" not found`

- [ ] **Step 3: Create the two interfaces**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * A Result DTO that can hydrate itself from a micro.bg row, whose wire keys are
 * PascalCase (with the documented exceptions `id`, `eMail` and `parentId`).
 */
interface FromMicroBg
{
    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static;
}
```

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * An Input DTO that can render itself as a micro.bg `functionData` object.
 * Null properties are omitted; properties the dialect has no field for are
 * dropped silently.
 */
interface ToMicroBg
{
    /** @return array<string, mixed> */
    public function toMicroBgArray(): array;
}
```

- [ ] **Step 4: Create `ItemAddCodeResult`**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Items;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * One extra code or barcode attached to an item, with the quantity ratio
 * between its measure and the item's base measure. micro.bg only.
 */
final class ItemAddCodeResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $measureId = null,
        public readonly ?string $code = null,
        public readonly ?int $codeType = null,
        public readonly ?float $ratio = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            measureId: isset($data['MeasureId']) ? (int) $data['MeasureId'] : null,
            code: isset($data['Code']) ? (string) $data['Code'] : null,
            codeType: isset($data['CodeType']) ? (int) $data['CodeType'] : null,
            ratio: isset($data['Ratio']) ? (float) $data['Ratio'] : null,
        );
    }
}
```

- [ ] **Step 5: Add the new fields and `fromMicroBg()` to the seven Result DTOs**

Follow the field table above. Every new constructor parameter goes **last** and defaults to `null` (`addCodes` defaults to `[]`). Each class gains `use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;` and `implements FromWarehousePro, FromMicroBg`.

Worked example — `PartnerResult` (the others follow the same shape against their own mapping row):

```php
    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['Code']) ? (string) $data['Code'] : null,
            company: isset($data['Name']) ? (string) $data['Name'] : null,
            company2: null,
            mol: isset($data['MOL']) ? (string) $data['MOL'] : null,
            mol2: null,
            city: isset($data['City']) ? (string) $data['City'] : null,
            city2: null,
            address: isset($data['Address']) ? (string) $data['Address'] : null,
            address2: null,
            phone: isset($data['Phone']) ? (string) $data['Phone'] : null,
            phone2: null,
            fax: null,
            email: isset($data['eMail']) ? (string) $data['eMail'] : null,
            taxId: isset($data['TaxID']) ? (string) $data['TaxID'] : null,
            vatId: isset($data['VatID']) ? (string) $data['VatID'] : null,
            bankName: null,
            bankCode: null,
            bankAcct: null,
            bankVatName: null,
            bankVatCode: null,
            bankVatAcct: null,
            priceGroup: isset($data['PriceGroup']) ? (int) $data['PriceGroup'] : null,
            discount: isset($data['Discount']) ? (float) $data['Discount'] : null,
            type: isset($data['PartnerType']) ? (int) $data['PartnerType'] : null,
            isVeryUsed: null,
            userId: null,
            groupId: isset($data['GroupId']) ? (int) $data['GroupId'] : null,
            userRealTime: null,
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
            cardNumber: isset($data['CardNumber']) ? (string) $data['CardNumber'] : null,
            note1: null,
            note2: null,
            paymentDays: null,
            contactPerson: isset($data['ContactPerson']) ? (string) $data['ContactPerson'] : null,
            partnerNote: isset($data['PartnerNote']) ? (string) $data['PartnerNote'] : null,
            groupName: isset($data['GroupName']) ? (string) $data['GroupName'] : null,
            groupPath: isset($data['GroupPath']) ? (string) $data['GroupPath'] : null,
            dateUpdated: isset($data['DateUpdated']) ? (string) $data['DateUpdated'] : null,
        );
    }
```

`ItemResult::fromMicroBg()` additionally builds the nested list:

```php
            addCodes: array_map(
                static fn (array $row): ItemAddCodeResult => ItemAddCodeResult::fromMicroBg($row),
                array_values(array_filter((array) ($data['AddCodes'] ?? []), 'is_array')),
            ),
```

- [ ] **Step 6: Add the new fields and `toMicroBgArray()` to the two Input DTOs**

`PartnerInput` and `ItemInput` gain the extra constructor parameters from the table (last, defaulting to `null`), `use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;` and `implements ToWarehousePro, ToMicroBg`. Follow the `toMicroBgArray()` mapping table; keep the existing `if ($this->x !== null)` style so null properties stay off the wire.

Worked example — the head of `PartnerInput::toMicroBgArray()`:

```php
    /** @return array<string, mixed> */
    public function toMicroBgArray(): array
    {
        $out = [];
        if ($this->id !== null) $out['id'] = $this->id;
        if ($this->code !== null) $out['Code'] = $this->code;
        if ($this->company !== null) $out['Name'] = $this->company;
        if ($this->mol !== null) $out['MOL'] = $this->mol;
        if ($this->city !== null) $out['City'] = $this->city;
        if ($this->address !== null) $out['Address'] = $this->address;
        if ($this->phone !== null) $out['Phone'] = $this->phone;
        if ($this->email !== null) $out['eMail'] = $this->email;
        if ($this->taxId !== null) $out['TaxID'] = $this->taxId;
        if ($this->vatId !== null) $out['VatID'] = $this->vatId;
        if ($this->priceGroup !== null) $out['PriceGroup'] = $this->priceGroup;
        if ($this->discount !== null) $out['Discount'] = $this->discount;
        if ($this->type !== null) $out['PartnerType'] = $this->type;
        if ($this->cardNumber !== null) $out['CardNumber'] = $this->cardNumber;
        if ($this->groupId !== null) $out['GroupId'] = $this->groupId;
        if ($this->deleted !== null) $out['Deleted'] = $this->deleted;
        if ($this->contactPerson !== null) $out['ContactPerson'] = $this->contactPerson;
        if ($this->partnerNote !== null) $out['PartnerNote'] = $this->partnerNote;

        return $out;
    }
```

- [ ] **Step 7: Run the whole suite**

Run: `composer test`
Expected: PASS — the new dialect tests plus every existing test (the Warehouse Pro dialect is untouched)

- [ ] **Step 8: Commit**

```bash
git add src/Contracts/Dto src/Dto tests/Dto
git commit -m "feat: teach the shared DTOs the micro.bg dialect"
```

---

### Task 6: Partners и Items ресурси

**Files:**
- Create: `src/MicroBg/Resources/Resource.php`, `Partners.php`, `Items.php`
- Test: `tests/MicroBg/Resources/PartnersTest.php`, `ItemsTest.php`

**Interfaces:**
- Consumes: `MicroBgTransport`, `PartnerRepository`, `ItemRepository`
- Produces:
  - `MicroBg\Resources\Resource::__construct(MicroBgTransport $transport)`, `protected const EACH_LIMIT = 100`
  - `Partners::list(?string $fromDate, ?int $fromId, ?int $limit, ?int $id, ?string $code, ?string $taxNo, ?string $email, ?string $phone, array $filters = []): ResultList<PartnerResult>`
  - `Partners::get(int $id)`, `create(PartnerInput $input, bool $autoGenerateCode = true)`, `update(int $id, PartnerInput $input)`, `delete(int $id): void`, `each(): iterable`
  - `Items::list(?string $fromDate, ?int $fromId, ?int $limit, ?int $id, ?string $code, ?string $barcode, ?bool $pricesWithVat, array $filters = []): ResultList<ItemResult>`
  - `Items::get`, `create`, `update`, `delete`, `each`, `quantities(?int $objectId = null, ?array $itemIds = null): ResultList<StoreResult>`, `prices(?int $priceGroup = null, ?bool $pricesWithVat = null, ?array $itemIds = null): ResultList<ItemResult>`

- [ ] **Step 1: Write the failing test**

First add the payload decoder to `tests/Pest.php` — Tasks 6, 7 and 10 all need it, so it belongs next to `fakeMicroBg()` rather than in whichever test file happens to load first:

```php
/**
 * Decode the signed payload of the last micro.bg request, so tests can assert
 * on functionName / parameters / functionData rather than on a base64 blob.
 *
 * @return array<string, mixed>
 */
function microBgPayload(FakeHttpClient $http): array
{
    parse_str((string) $http->lastRequest()->getBody(), $fields);

    return json_decode(base64_decode(urldecode(substr($fields['Request'], 0, -64))), true);
}
```

`tests/MicroBg/Resources/PartnersTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists partners with the documented parameters', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    fakeMicroBg($http)->partners()->list(fromDate: '2018-06-01 14:00:00', limit: 50);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'getPartners',
        'parameters' => ['fromDate' => '2018-06-01 14:00:00', 'limit' => 50],
        'functionData' => null,
    ]);
});

it('gets one partner by id', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['id' => 17, 'Name' => 'ACME']]]);

    $partner = fakeMicroBg($http)->partners()->get(17);

    expect($partner->company)->toBe('ACME')
        ->and(microBgPayload($http)['parameters'])->toBe(['Id' => 17]);
});

it('throws when a partner is not found', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    expect(fn () => fakeMicroBg($http)->partners()->get(999))
        ->toThrow(ApiException::class, 'Partner 999 was not found');
});

it('creates a partner and asks for a generated code by default', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => ['id' => 92729, 'Name' => 'ACME']]);

    fakeMicroBg($http)->partners()->create(new PartnerInput(company: 'ACME'));

    expect(microBgPayload($http))->toBe([
        'functionName' => 'insertPartner',
        'parameters' => ['AutoGenerateCode' => 1],
        'functionData' => ['Name' => 'ACME'],
    ]);
});

it('updates a partner by putting the id inside functionData', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => ['id' => 92729]]);

    fakeMicroBg($http)->partners()->update(92729, new PartnerInput(address: 'ул. Лале, №21'));

    expect(microBgPayload($http))->toBe([
        'functionName' => 'editPartner',
        'parameters' => [],
        'functionData' => ['Address' => 'ул. Лале, №21', 'id' => 92729],
    ]);
});

it('deletes a partner', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => null]);

    fakeMicroBg($http)->partners()->delete(92729);

    expect(microBgPayload($http)['parameters'])->toBe(['Id' => 92729]);
});

it('walks partners with a fromId cursor', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 1], ['id' => 2]]]),
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 5]]]),
    );

    $ids = [];
    foreach (fakeMicroBg($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([1, 2, 5])
        ->and($http->received)->toHaveCount(2);
});

it('stops walking when a page has no usable cursor', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['Name' => 'no id']]]);

    $ids = [];
    foreach (fakeMicroBg($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([null])
        ->and($http->received)->toHaveCount(1);
});
```

`tests/MicroBg/Resources/ItemsTest.php` mirrors the CRUD assertions against `getItems` / `insertItem` / `editItem` / `deleteItem`, plus:

```php
it('reads quantities for one object', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['ItemId' => 8681, 'Qtty' => -62.0]]]);

    $rows = fakeMicroBg($http)->items()->quantities(objectId: 1232, itemIds: [8681]);

    expect($rows->first()->goodId)->toBe('8681')
        ->and($rows->first()->qtty)->toBe(-62.0)
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'getItemQuantities',
            'parameters' => ['ObjectId' => 1232, 'ItemIds' => [8681]],
            'functionData' => null,
        ]);
});

it('reads prices for a price group', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => [['id' => 8681, 'Code' => 174, 'TaxValue' => 20, 'Price' => 12.0]]]);

    fakeMicroBg($http)->items()->prices(priceGroup: 2, pricesWithVat: true, itemIds: [8681, 8682]);

    expect(microBgPayload($http)['parameters'])
        ->toBe(['PriceGroup' => 2, 'PricesWithVat' => 1, 'ItemIds' => [8681, 8682]]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/Resources`
Expected: FAIL — `Call to undefined function fakeMicroBg()` (the helper lands in Task 8; until then these tests fail)

**Note for the implementer:** add the `fakeMicroBg()` helper to `tests/Pest.php` as part of this task rather than waiting for Task 8, otherwise nothing here is runnable:

```php
use Ux2Dev\Microinvest\MicroBg\MicroBgClient;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;

function fakeMicroBg(FakeHttpClient $http, string $apiId = 'api-id', string $secretKey = 'secret'): MicroBgClient
{
    $factory = FakeHttpClient::factory();

    return new MicroBgClient(new MicroBgConfig($apiId, $secretKey), $http, $factory, $factory);
}
```

Since it needs `MicroBgClient`, build a minimal version of that class here (constructor + `partners()` + `items()` lazy getters) and let Task 8 add the contract and the remaining accessors.

- [ ] **Step 3: Write the resource base**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\MicroBg\MicroBgTransport;

abstract class Resource
{
    /** Batch size used by the contract-level each() walkers. */
    protected const EACH_LIMIT = 100;

    public function __construct(protected readonly MicroBgTransport $transport)
    {
    }
}
```

- [ ] **Step 4: Write `Partners`**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Http\ResultList;

final class Partners extends Resource implements PartnerRepository
{
    /** @return ResultList<PartnerResult> */
    public function list(
        ?string $fromDate = null,
        ?int $fromId = null,
        ?int $limit = null,
        ?int $id = null,
        ?string $code = null,
        ?string $taxNo = null,
        ?string $email = null,
        ?string $phone = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->callList('getPartners', array_merge([
            'fromDate' => $fromDate,
            'fromId' => $fromId,
            'limit' => $limit,
            'Id' => $id,
            'Code' => $code,
            'TaxNo' => $taxNo,
            'Email' => $email,
            'Phone' => $phone,
        ], $filters), PartnerResult::class);
    }

    public function get(int $id): PartnerResult
    {
        $partner = $this->list(id: $id)->first();

        if ($partner === null) {
            throw new ApiException("Partner {$id} was not found", httpStatus: 200);
        }

        return $partner;
    }

    /**
     * PDF v1.4 defaults AutoGenerateCode to 0, which makes an insert fail when
     * the code collides. The SDK defaults to 1 instead; pass false to opt out.
     */
    public function create(PartnerInput $input, bool $autoGenerateCode = true): PartnerResult
    {
        return $this->transport->callOne(
            'insertPartner',
            ['AutoGenerateCode' => $autoGenerateCode ? 1 : 0],
            $input->toMicroBgArray(),
            PartnerResult::class,
        );
    }

    public function update(int $id, PartnerInput $input): PartnerResult
    {
        $data = $input->toMicroBgArray();
        $data['id'] = $id;

        return $this->transport->callOne('editPartner', [], $data, PartnerResult::class);
    }

    public function delete(int $id): void
    {
        $this->transport->call('deletePartner', ['Id' => $id]);
    }

    /** @return iterable<PartnerResult> */
    public function each(): iterable
    {
        $fromId = 0;

        while (true) {
            $batch = $this->list(fromId: $fromId, limit: self::EACH_LIMIT);
            $cursor = $fromId;

            foreach ($batch as $partner) {
                yield $partner;

                if ($partner->id !== null && $partner->id > $cursor) {
                    $cursor = $partner->id;
                }
            }

            // No rows, a short page, or no usable cursor: we are done.
            if ($batch->count() < self::EACH_LIMIT || $cursor === $fromId) {
                return;
            }

            $fromId = $cursor;
        }
    }
}
```

- [ ] **Step 5: Write `Items`**

```php
final class Items extends Resource implements ItemRepository
{
    /** @return ResultList<ItemResult> */
    public function list(
        ?string $fromDate = null,
        ?int $fromId = null,
        ?int $limit = null,
        ?int $id = null,
        ?string $code = null,
        ?string $barcode = null,
        ?bool $pricesWithVat = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->callList('getItems', array_merge([
            'fromDate' => $fromDate,
            'fromId' => $fromId,
            'limit' => $limit,
            'Id' => $id,
            'Code' => $code,
            'Barcode' => $barcode,
            'PricesWithVat' => $pricesWithVat === null ? null : ($pricesWithVat ? 1 : 0),
        ], $filters), ItemResult::class);
    }

    public function get(int $id): ItemResult
    {
        $item = $this->list(id: $id)->first();

        if ($item === null) {
            throw new ApiException("Item {$id} was not found", httpStatus: 200);
        }

        return $item;
    }

    /** @see Partners::create() for why AutoGenerateCode defaults to 1. */
    public function create(ItemInput $input, bool $autoGenerateCode = true): ItemResult
    {
        return $this->transport->callOne(
            'insertItem',
            ['AutoGenerateCode' => $autoGenerateCode ? 1 : 0],
            $input->toMicroBgArray(),
            ItemResult::class,
        );
    }

    public function update(int $id, ItemInput $input): ItemResult
    {
        $data = $input->toMicroBgArray();
        $data['id'] = $id;

        return $this->transport->callOne('editItem', [], $data, ItemResult::class);
    }

    public function delete(int $id): void
    {
        $this->transport->call('deleteItem', ['Id' => $id]);
    }

    /** @return iterable<ItemResult> */
    public function each(): iterable
    {
        $fromId = 0;

        while (true) {
            $batch = $this->list(fromId: $fromId, limit: self::EACH_LIMIT);
            $cursor = $fromId;

            foreach ($batch as $item) {
                yield $item;

                if ($item->id !== null && $item->id > $cursor) {
                    $cursor = $item->id;
                }
            }

            if ($batch->count() < self::EACH_LIMIT || $cursor === $fromId) {
                return;
            }

            $fromId = $cursor;
        }
    }
```

plus the two micro.bg-only readers:

```php
    /** @param list<int>|null $itemIds @return ResultList<StoreResult> */
    public function quantities(?int $objectId = null, ?array $itemIds = null): ResultList
    {
        return $this->transport->callList('getItemQuantities', [
            'ObjectId' => $objectId,
            'ItemIds' => $itemIds,
        ], StoreResult::class);
    }

    /** @param list<int>|null $itemIds @return ResultList<ItemResult> */
    public function prices(?int $priceGroup = null, ?bool $pricesWithVat = null, ?array $itemIds = null): ResultList
    {
        return $this->transport->callList('getItemPrices', [
            'PriceGroup' => $priceGroup,
            'PricesWithVat' => $pricesWithVat === null ? null : ($pricesWithVat ? 1 : 0),
            'ItemIds' => $itemIds,
        ], ItemResult::class);
    }
```

- [ ] **Step 6: Run the resource tests**

Run: `vendor/bin/pest tests/MicroBg`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add src/MicroBg tests/MicroBg tests/Pest.php
git commit -m "feat: add micro.bg partner and item resources"
```

---

### Task 7: Групи и справочници

**Files:**
- Create: `src/MicroBg/Resources/Groups.php`, `TaxGroups.php`, `Payments.php`, `Objects.php`
- Test: `tests/MicroBg/Resources/LookupsTest.php`

**Interfaces:**
- Produces:
  - `Groups::list(string $module): ResultList<NomenclatureGroupResult>` — `$module` е `'Items'` или `'Partners'`
  - `Groups::create(string $module, string $name, ?int $parentId = null, ?string $path = null): NomenclatureGroupResult`
  - `Groups::rename(string $module, int $id, string $name): NomenclatureGroupResult`
  - `Groups::delete(string $module, string $mode, ?int $id = null, ?string $path = null): void`
  - `TaxGroups::list(): ResultList<VatGroupResult>`
  - `Payments::types(): ResultList<PaymentTypeResult>`
  - `Objects::list(): ResultList<LocationResult>`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('routes each lookup to its documented function name', function (string $call, array $args, string $function, array $parameters) {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => []]);

    $client = fakeMicroBg($http);
    [$resource, $method] = explode('.', $call);
    $client->{$resource}()->{$method}(...$args);

    expect(microBgPayload($http)['functionName'])->toBe($function)
        ->and(microBgPayload($http)['parameters'])->toBe($parameters);
})->with([
    ['groups.list', ['Items'], 'getGroups', ['Module' => 'Items']],
    ['groups.list', ['Partners'], 'getGroups', ['Module' => 'Partners']],
    ['taxGroups.list', [], 'getTaxGroups', []],
    ['payments.types', [], 'getPaymentTypes', []],
    ['objects.list', [], 'getObjects', []],
]);

it('creates a group under a parent', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => ['id' => 15310, 'Name' => 'Спортни стоки', 'Path' => 'ААЕ']]);

    $group = fakeMicroBg($http)->groups()->create('Items', 'Спортни стоки', parentId: 2345);

    expect($group->id)->toBe(15310)
        ->and($group->path)->toBe('ААЕ')
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'insertGroup',
            'parameters' => ['Module' => 'Items', 'parentId' => 2345],
            'functionData' => ['Name' => 'Спортни стоки'],
        ]);
});

it('renames a group', function () {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => ['id' => 1765, 'Name' => 'Спортни стоки']]);

    fakeMicroBg($http)->groups()->rename('Items', 1765, 'Спортни стоки');

    expect(microBgPayload($http))->toBe([
        'functionName' => 'renameGroup',
        'parameters' => ['Module' => 'Items', 'Id' => 1765],
        'functionData' => ['Name' => 'Спортни стоки'],
    ]);
});

it('deletes a group by id, by path or wholesale', function (array $args, array $parameters) {
    $http = FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => null]);

    fakeMicroBg($http)->groups()->delete(...$args);

    expect(microBgPayload($http)['parameters'])->toBe($parameters);
})->with([
    [['Items', 'ById', 1765], ['Module' => 'Items', 'Mode' => 'ById', 'Id' => 1765]],
    [['Items', 'ByPath', null, 'ААВ'], ['Module' => 'Items', 'Mode' => 'ByPath', 'Path' => 'ААВ']],
    [['Items', 'All'], ['Module' => 'Items', 'Mode' => 'All']],
]);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroBg/Resources/LookupsTest.php`
Expected: FAIL — `Call to undefined method ...MicroBgClient::groups()`

- [ ] **Step 3: Write the four resources**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * The partner and item group trees. `$module` is 'Items' or 'Partners'.
 */
final class Groups extends Resource
{
    /** @return ResultList<NomenclatureGroupResult> */
    public function list(string $module): ResultList
    {
        return $this->transport->callList('getGroups', ['Module' => $module], NomenclatureGroupResult::class);
    }

    public function create(
        string $module,
        string $name,
        ?int $parentId = null,
        ?string $path = null,
    ): NomenclatureGroupResult {
        return $this->transport->callOne(
            'insertGroup',
            ['Module' => $module, 'parentId' => $parentId, 'Path' => $path],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    public function rename(string $module, int $id, string $name): NomenclatureGroupResult
    {
        return $this->transport->callOne(
            'renameGroup',
            ['Module' => $module, 'Id' => $id],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    /** @param 'ById'|'ByPath'|'All' $mode */
    public function delete(string $module, string $mode, ?int $id = null, ?string $path = null): void
    {
        $this->transport->call('deleteGroup', [
            'Module' => $module,
            'Mode' => $mode,
            'Id' => $id,
            'Path' => $path,
        ]);
    }
}
```

`TaxGroups`, `Payments` and `Objects` are one method each:

```php
final class TaxGroups extends Resource
{
    /** @return ResultList<VatGroupResult> */
    public function list(): ResultList
    {
        return $this->transport->callList('getTaxGroups', [], VatGroupResult::class);
    }
}
```

```php
final class Payments extends Resource
{
    /** @return ResultList<PaymentTypeResult> */
    public function types(): ResultList
    {
        return $this->transport->callList('getPaymentTypes', [], PaymentTypeResult::class);
    }
}
```

```php
final class Objects extends Resource
{
    /** @return ResultList<LocationResult> */
    public function list(): ResultList
    {
        return $this->transport->callList('getObjects', [], LocationResult::class);
    }
}
```

- [ ] **Step 4: Add the four lazy accessors to `MicroBgClient`**

```php
    public function groups(): Groups
    {
        return $this->groups ??= new Groups($this->transport);
    }

    public function taxGroups(): TaxGroups
    {
        return $this->taxGroups ??= new TaxGroups($this->transport);
    }

    public function payments(): Payments
    {
        return $this->payments ??= new Payments($this->transport);
    }

    public function objects(): Objects
    {
        return $this->objects ??= new Objects($this->transport);
    }
```

- [ ] **Step 5: Run the tests**

Run: `vendor/bin/pest tests/MicroBg`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add src/MicroBg tests/MicroBg
git commit -m "feat: add micro.bg group and lookup resources"
```

---

### Task 8: `MicroBgClient implements Contracts\Client` + conformance тест

Тук се плаща сметката за целия дизайн: един и същ набор твърдения срещу двете реализации.

**Files:**
- Modify: `src/MicroBg/MicroBgClient.php`
- Create: `tests/ConformanceTest.php`

**Interfaces:**
- Produces: `MicroBgClient implements Contracts\Client` с `partners()`, `items()`, `groups()`, `taxGroups()`, `payments()`, `objects()` и шестте `list*` метода

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

/**
 * Both backends must behave identically through the shared contract. Each row
 * is a factory that wires the backend to a fake client returning the same
 * logical payload in that backend's own envelope.
 */
dataset('backends', [
    'warehouse_pro' => [
        fn (array $rows) => fakeWarehousePro(FakeHttpClient::withJson($rows)),
        fn (array $rows) => $rows,
    ],
    'micro_bg' => [
        fn (array $rows) => fakeMicroBg(FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => $rows])),
        fn (array $rows) => $rows,
    ],
]);

it('satisfies the Client contract', function (callable $make) {
    $client = $make([]);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->partners())->toBeInstanceOf(PartnerRepository::class)
        ->and($client->items())->toBeInstanceOf(ItemRepository::class);
})->with('backends');

it('exposes every lookup on the contract', function (callable $make) {
    $client = $make([]);

    foreach (['listItemGroups', 'listPartnerGroups', 'listTaxGroups', 'listPaymentTypes', 'listObjects'] as $method) {
        expect($client->{$method}()->all())->toBe([]);
    }

    expect($client->listQuantities()->all())->toBe([])
        ->and($client->listQuantities(4)->all())->toBe([]);
})->with('backends');

it('returns an empty walk for an empty nomenclature', function (callable $make) {
    $client = $make([]);

    expect(iterator_to_array($client->partners()->each()))->toBe([])
        ->and(iterator_to_array($client->items()->each()))->toBe([]);
})->with('backends');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/ConformanceTest.php`
Expected: FAIL — `MicroBgClient is not an instance of Client`

- [ ] **Step 3: Complete `MicroBgClient`**

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;
use Ux2Dev\Microinvest\MicroBg\Resources\Groups;
use Ux2Dev\Microinvest\MicroBg\Resources\Items;
use Ux2Dev\Microinvest\MicroBg\Resources\Objects;
use Ux2Dev\Microinvest\MicroBg\Resources\Partners;
use Ux2Dev\Microinvest\MicroBg\Resources\Payments;
use Ux2Dev\Microinvest\MicroBg\Resources\TaxGroups;

/**
 * Client for the micro.bg External App API — the hosted Microinvest service.
 *
 * One instance per registered external application. Methods that exist only
 * here (item prices, cost allocation, invoices, company data) are deliberately
 * absent from Contracts\Client.
 */
final class MicroBgClient implements Client
{
    public readonly MicroBgTransport $transport;

    private ?Partners $partners = null;
    private ?Items $items = null;
    private ?Groups $groups = null;
    private ?TaxGroups $taxGroups = null;
    private ?Payments $payments = null;
    private ?Objects $objects = null;

    public function __construct(
        MicroBgConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->transport = new MicroBgTransport($config, $httpClient, $requestFactory, $streamFactory);
    }

    public function partners(): Partners
    {
        return $this->partners ??= new Partners($this->transport);
    }

    public function items(): Items
    {
        return $this->items ??= new Items($this->transport);
    }

    // groups(), taxGroups(), payments(), objects() as added in Task 7

    /** @return ResultList<NomenclatureGroupResult> */
    public function listItemGroups(): ResultList
    {
        return $this->groups()->list('Items');
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList
    {
        return $this->groups()->list('Partners');
    }

    /** @return ResultList<VatGroupResult> */
    public function listTaxGroups(): ResultList
    {
        return $this->taxGroups()->list();
    }

    /** @return ResultList<PaymentTypeResult> */
    public function listPaymentTypes(): ResultList
    {
        return $this->payments()->types();
    }

    /** @return ResultList<LocationResult> */
    public function listObjects(): ResultList
    {
        return $this->objects()->list();
    }

    /** @return ResultList<StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList
    {
        return $this->items()->quantities($objectId);
    }
}
```

- [ ] **Step 4: Run the whole suite**

Run: `composer test`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/MicroBg/MicroBgClient.php tests/ConformanceTest.php
git commit -m "feat: make MicroBgClient satisfy the shared Client contract"
```

---

### Task 9: Laravel драйвер, фабрика и README

**Files:**
- Modify: `src/Microinvest.php`, `src/Laravel/MicroinvestManager.php`, `src/Laravel/config/microinvest.php`, `README.md`
- Test: `tests/Laravel/ManagerTest.php`, `tests/MicroinvestFactoryTest.php`

**Interfaces:**
- Produces: `Microinvest::microBg(MicroBgConfig $config, ClientInterface $httpClient, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory): MicroBgClient`; драйвер `micro_bg` с ключове `api_id`, `secret_key`, `entry_point`, `timeout`

- [ ] **Step 1: Write the failing tests**

Append to `tests/MicroinvestFactoryTest.php`:

```php
it('builds a micro.bg client', function () {
    $factory = FakeHttpClient::factory();

    $client = Microinvest::microBg(
        new Ux2Dev\Microinvest\MicroBg\MicroBgConfig('api-id', 'secret'),
        FakeHttpClient::withJson([]),
        $factory,
        $factory,
    );

    expect($client)->toBeInstanceOf(Ux2Dev\Microinvest\MicroBg\MicroBgClient::class)
        ->and($client)->toBeInstanceOf(Client::class);
});
```

Append to `tests/Laravel/ManagerTest.php`:

```php
it('builds a micro.bg connection from the driver key', function () {
    $manager = new MicroinvestManager([
        'default' => 'online',
        'connections' => [
            'online' => [
                'driver' => 'micro_bg',
                'api_id' => '1821761530712553',
                'secret_key' => 'a6808173988b',
                'timeout' => 15,
            ],
        ],
    ]);

    $client = $manager->client();

    expect($client)->toBeInstanceOf(Ux2Dev\Microinvest\MicroBg\MicroBgClient::class)
        ->and($client->transport->config->apiId)->toBe('1821761530712553')
        ->and($client->transport->config->timeout)->toBe(15);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Laravel/ManagerTest.php tests/MicroinvestFactoryTest.php`
Expected: FAIL — `Unknown Microinvest driver "micro_bg"`

- [ ] **Step 3: Add the factory method**

In `src/Microinvest.php`:

```php
    public static function microBg(
        MicroBgConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ): MicroBgClient {
        return new MicroBgClient($config, $httpClient, $requestFactory, $streamFactory);
    }
```

- [ ] **Step 4: Add the driver branch**

In `src/Laravel/MicroinvestManager.php` extend the `match` and add the builder:

```php
        return match ($driver) {
            'warehouse_pro' => $this->buildWarehousePro($c),
            'micro_bg' => $this->buildMicroBg($c),
            default => throw new ConfigurationException("Unknown Microinvest driver \"{$driver}\""),
        };
```

```php
    /** @param array<string, mixed> $c */
    private function buildMicroBg(array $c): MicroBgClient
    {
        $config = new MicroBgConfig(
            apiId: (string) ($c['api_id'] ?? ''),
            secretKey: (string) ($c['secret_key'] ?? ''),
            entryPoint: (string) ($c['entry_point'] ?? MicroBgConfig::DEFAULT_ENTRY_POINT),
            timeout: (int) ($c['timeout'] ?? 30),
        );

        $factory = new HttpFactory();

        return new MicroBgClient(
            $config,
            $this->httpClient ?? new \GuzzleHttp\Client(['timeout' => $config->timeout]),
            $this->requestFactory ?? $factory,
            $this->streamFactory ?? $factory,
        );
    }
```

- [ ] **Step 5: Add the connection to the shipped config**

```php
        'online' => [
            'driver'      => 'micro_bg',
            'api_id'      => env('MICROBG_API_ID'),
            'secret_key'  => env('MICROBG_SECRET_KEY'),
            'entry_point' => env('MICROBG_ENTRY_POINT', 'https://micro.bg/ExtApps/ExternalApp/API/'),
            'timeout'     => (int) env('MICROBG_TIMEOUT', 30),
        ],
```

- [ ] **Step 6: Update the README**

Retitle to `# Microinvest PHP SDK`, describe both backends in the opening paragraph, add a micro.bg quick-start block mirroring the Warehouse Pro one (`Microinvest::microBg(new MicroBgConfig($apiId, $secretKey), ...)`), extend the architecture table with the `MicroBg\*` rows, document the `micro_bg` Laravel driver, and add a short "Which backend supports what" table listing the contract methods plus each backend's extras.

- [ ] **Step 7: Run the whole suite**

Run: `composer test`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add src tests README.md
git commit -m "feat: wire the micro.bg driver into Laravel and the factory"
```

---

## Definition of done

- [ ] `composer test` зелен
- [ ] `tests/ConformanceTest.php` минава и за двата backend-а
- [ ] `grep -rn 'TODO\|FIXME' src/` не връща нищо
- [ ] Всяко несъответствие в PDF-а е коментирано в кода с `// PDF v1.4: ...`

## Какво остава за Фаза 3

Само за micro.bg, извън контракта: `Operations` (`getOperations`, `saveOperation`, `deleteOperation`, `createCostAllocation`), `Payments::add()` (`addPayment`), `Invoices::create()` (`createInvoice`), `Company` (`getCompanyData`, `getBankAccounts`), плюс `OperationInput`/`PaymentInput`/`DocumentInput` в micro.bg диалект и новите Result DTO-та за фирма, банкови сметки и фактура.

## Открити въпроси към Микроинвест

1. `getMeasures()` се цитира в `insertItem()`, но не е документиран никъде — без него `MeasureId` е познаваем само чрез вече съществуваща стока.
2. Секция 5.2 липсва в PDF-а — след `5.1 getOperations()` идва направо `5.3 saveOperation()`.
3. `status` срещу `success` — имплементирано е приемане и на двете.
4. При партньори `Deleted` е документиран като „1 - да, 2 - не", а при стоки като „1 - да, 0 - не". Имплементирано е като `(bool)`, тоест 2 би се чело като „да" — нужно е потвърждение.
5. Формат на `errors[]` при реална грешка — примерите показват само празен масив.
