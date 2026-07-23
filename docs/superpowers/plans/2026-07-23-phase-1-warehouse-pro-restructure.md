# Фаза 1: Реорганизация на Warehouse Pro + извличане на контрактите

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Пренарежда съществуващия Warehouse Pro SDK в поддърво `src/WarehousePro/`, въвежда диалектните DTO интерфейси и контрактите `Client`/`PartnerRepository`/`ItemRepository` с `each()` — без да добавя нито ред micro.bg код.

**Architecture:** Днешният код се мести 1:1 в `WarehousePro/` (`git mv` + смяна на namespace, нула промяна в логиката). `Exception/`, `Http/ResultList`, `Dto/` остават споделени в корена. Хидратацията `fromArray()`/`toArray()` се преименува на диалектно-специфични `fromWarehousePro()`/`toWarehouseProArray()` зад маркерни интерфейси, за да може Фаза 2 да добави `fromMicroBg()` към същите DTO класове. `Microinvest` спира да е клиент и става статична фабрика.

**Tech Stack:** PHP 8.2+, PSR-18 client + PSR-17 factories (инжектирани, без discovery), Pest 4, PHPUnit coverage през PCOV в CI.

## Global Constraints

- PHP `>=8.2`; всеки файл започва с `declare(strict_types=1);`
- Всички конкретни класове са `final`; DTO-тата остават `readonly` където са такива днес
- PSR-18/PSR-17 се **инжектират** — никакъв discovery, никакъв `new Client()` извън `src/Laravel/`
- Никакви нови зависимости в `require` (Guzzle остава само `require-dev` + Laravel manager)
- Wire ключовете на Warehouse Pro са snake_case и **не се променят** от този план
- Покритието трябва да остане 100% (`composer test:coverage` в CI); локално не може да се мери — Herd CLI няма Xdebug/PCOV, разчита се на CI
- Работи се в клон `feat/micro-bg-backend` (вече създаден, съдържа спецификацията)

**Референтна спецификация:** `docs/superpowers/specs/2026-07-23-two-backend-sdk-design.md`

## Файлова структура след Фаза 1

| Път | Отговорност |
|---|---|
| `src/Contracts/Client.php` | какво умеят и двата backend-а на ниво клиент |
| `src/Contracts/PartnerRepository.php` | CRUD + `each()` за партньори |
| `src/Contracts/ItemRepository.php` | CRUD + `each()` за стоки |
| `src/Contracts/Dto/FromWarehousePro.php` | маркер: Result DTO знае да чете snake_case ред |
| `src/Contracts/Dto/ToWarehousePro.php` | маркер: Input DTO знае да пише snake_case тяло |
| `src/WarehousePro/WarehouseProConfig.php` | преместен `MicroinvestConfig` |
| `src/WarehousePro/WarehouseProTransport.php` | преместен `MicroinvestTransport` |
| `src/WarehousePro/WarehouseProClient.php` | преместен `Microinvest`, вече `implements Client` |
| `src/WarehousePro/Resources/*.php` | преместени ресурси |
| `src/Microinvest.php` | статична фабрика |
| `src/Dto/`, `src/Exception/`, `src/Http/ResultList.php` | споделени, остават на място |

---

### Task 1: Опашка от отговори във `FakeHttpClient`

Днешният `FakeHttpClient` връща **един и същ** отговор на всяка заявка. `each()` прави N заявки с различни отговори, така че без опашка Task 4 не може да се тества.

**Files:**
- Modify: `tests/Http/FakeHttpClient.php`
- Test: `tests/Http/FakeHttpClientTest.php` (създава се)

**Interfaces:**
- Consumes: нищо
- Produces: `FakeHttpClient::jsonResponse(array $body, int $status = 200, array $headers = []): Response`, `FakeHttpClient::sequence(ResponseInterface ...$responses): self`

- [ ] **Step 1: Write the failing test**

Create `tests/Http/FakeHttpClientTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('returns queued responses in order', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse([['id' => 1]], headers: ['X-TotalPages' => '2']),
        FakeHttpClient::jsonResponse([['id' => 2]], headers: ['X-TotalPages' => '2']),
    );

    $factory = FakeHttpClient::factory();
    $first = $http->sendRequest($factory->createRequest('GET', 'http://x/1'));
    $second = $http->sendRequest($factory->createRequest('GET', 'http://x/2'));

    expect((string) $first->getBody())->toBe('[{"id":1}]')
        ->and((string) $second->getBody())->toBe('[{"id":2}]')
        ->and($second->getHeaderLine('X-TotalPages'))->toBe('2')
        ->and($http->received)->toHaveCount(2);
});

it('falls back to the default empty body once the queue is drained', function () {
    $http = FakeHttpClient::sequence(FakeHttpClient::jsonResponse([['id' => 1]]));

    $factory = FakeHttpClient::factory();
    $http->sendRequest($factory->createRequest('GET', 'http://x/1'));
    $drained = $http->sendRequest($factory->createRequest('GET', 'http://x/2'));

    expect((string) $drained->getBody())->toBe('[]');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Http/FakeHttpClientTest.php`
Expected: FAIL — `Call to undefined method ...FakeHttpClient::sequence()`

- [ ] **Step 3: Implement the queue**

In `tests/Http/FakeHttpClient.php`, add the property, drain it in `sendRequest`, and add the two static helpers. `withJson` is refactored to reuse `jsonResponse` (DRY):

```php
    /** @var list<ResponseInterface> */
    private array $queue = [];

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->received[] = $request;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->queue !== []) {
            return array_shift($this->queue);
        }

        return $this->response ?? new Response(200, [], '[]');
    }

    /**
     * @param  array<mixed>          $body
     * @param  array<string, string> $headers
     */
    public static function jsonResponse(array $body, int $status = 200, array $headers = []): Response
    {
        return new Response(
            status: $status,
            headers: $headers + ['Content-Type' => 'application/json'],
            body: json_encode($body, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @param  array<mixed>          $body
     * @param  array<string, string> $headers
     */
    public static function withJson(array $body, int $status = 200, array $headers = []): self
    {
        return new self(self::jsonResponse($body, $status, $headers));
    }

    public static function sequence(ResponseInterface ...$responses): self
    {
        $fake = new self();
        $fake->queue = array_values($responses);

        return $fake;
    }
```

- [ ] **Step 4: Run the whole suite**

Run: `composer test`
Expected: PASS — новите 2 теста минават, старите остават зелени (`withJson` е рефакториран, не променен по поведение)

- [ ] **Step 5: Commit**

```bash
git add tests/Http/FakeHttpClient.php tests/Http/FakeHttpClientTest.php
git commit -m "test: let FakeHttpClient serve a queue of responses"
```

---

### Task 2: Диалектни интерфейси за хидратация

Преименува `fromArray()` → `fromWarehousePro()` и `toArray()` → `toWarehouseProArray()` във всички DTO-та, зад два маркерни интерфейса. Това освобождава името за `fromMicroBg()` във Фаза 2 и прави двойката транспорт↔DTO проверима от PHPStan.

**Files:**
- Create: `src/Contracts/Dto/FromWarehousePro.php`, `src/Contracts/Dto/ToWarehousePro.php`
- Modify: 11 Result DTO-та в `src/Dto/Result/`, 5 Input DTO-та в `src/Dto/Input/`
- Modify: `src/Http/MicroinvestTransport.php:116,138,170`
- Modify: `src/Resources/{Items,Partners,Payments,Documents,Operations}.php` (извикванията на `toArray()`)
- Test: `tests/Resources/ReadOnlyResourcesTest.php:63,66` (два call site-а)

**Interfaces:**
- Consumes: нищо
- Produces:
  - `Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro::fromWarehousePro(array $data): static`
  - `Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro::toWarehouseProArray(): array`

- [ ] **Step 1: Write the failing test**

Create `tests/Dto/DialectContractsTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
use Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;

it('hydrates a partner from a Warehouse Pro row', function () {
    $p = PartnerResult::fromWarehousePro(['id' => 7, 'company' => 'ACME', 'tax_id' => '831826092']);

    expect($p)->toBeInstanceOf(FromWarehousePro::class)
        ->and($p->id)->toBe(7)
        ->and($p->company)->toBe('ACME')
        ->and($p->taxId)->toBe('831826092');
});

it('serialises a partner to Warehouse Pro wire keys', function () {
    $input = new PartnerInput(company: 'ACME', taxId: '831826092', priceGroup: 2);

    expect($input)->toBeInstanceOf(ToWarehousePro::class)
        ->and($input->toWarehouseProArray())->toBe([
            'company' => 'ACME',
            'tax_id' => '831826092',
            'price_group' => 2,
        ]);
});

it('marks every Result DTO as Warehouse Pro hydratable', function (string $file) {
    $class = 'Ux2Dev\\Microinvest\\Dto\\Result\\' . str_replace('/', '\\', substr($file, 0, -4));

    expect(is_a($class, FromWarehousePro::class, allow_string: true))->toBeTrue();
})->with(function () {
    $base = __DIR__ . '/../../src/Dto/Result/';
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $f) {
        if ($f->getExtension() === 'php') {
            $files[] = str_replace($base, '', $f->getPathname());
        }
    }
    sort($files);

    return $files;
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Dto/DialectContractsTest.php`
Expected: FAIL — `Interface "Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro" not found`

- [ ] **Step 3: Create the two interfaces**

`src/Contracts/Dto/FromWarehousePro.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * A Result DTO that can hydrate itself from a Warehouse Pro REST row, whose
 * wire keys are snake_case.
 */
interface FromWarehousePro
{
    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static;
}
```

`src/Contracts/Dto/ToWarehousePro.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * An Input DTO that can render itself as a Warehouse Pro REST request body,
 * using snake_case wire keys. Null properties are omitted.
 */
interface ToWarehousePro
{
    /** @return array<string, mixed> */
    public function toWarehouseProArray(): array;
}
```

- [ ] **Step 4: Rename the hydration method on all 11 Result DTOs**

For each of these files:

```
src/Dto/Result/Documents/DocumentResult.php
src/Dto/Result/Items/ItemResult.php
src/Dto/Result/Locations/LocationResult.php
src/Dto/Result/NomenclatureGroupResult.php
src/Dto/Result/Operations/OperationResult.php
src/Dto/Result/Partners/PartnerResult.php
src/Dto/Result/Payments/PaymentResult.php
src/Dto/Result/Payments/PaymentTypeResult.php
src/Dto/Result/Store/StoreResult.php
src/Dto/Result/Users/UserResult.php
src/Dto/Result/VatGroups/VatGroupResult.php
```

apply exactly three edits — add the import, add `implements`, rename the method. The body of the method is untouched. Worked example for `PartnerResult`:

```php
 namespace Ux2Dev\Microinvest\Dto\Result\Partners;

+use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
+
 /**
  * A partner row (table partners).
  */
-final class PartnerResult
+final class PartnerResult implements FromWarehousePro
 {
     ...
     /** @param array<string, mixed> $data */
-    public static function fromArray(array $data): static
+    public static function fromWarehousePro(array $data): static
     {
```

- [ ] **Step 5: Rename the serialisation method on all 5 Input DTOs**

For each of:

```
src/Dto/Input/Documents/DocumentInput.php
src/Dto/Input/Items/ItemInput.php
src/Dto/Input/Operations/OperationInput.php
src/Dto/Input/Partners/PartnerInput.php
src/Dto/Input/Payments/PaymentInput.php
```

apply the same shape of edit. Worked example for `PartnerInput`:

```php
 namespace Ux2Dev\Microinvest\Dto\Input\Partners;

+use Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro;
+
 /**
  * Input DTO for creating (POST /Partner) or updating (PUT /Partner) a partner.
  * Only non-null properties are sent on the wire.
  */
-final readonly class PartnerInput
+final readonly class PartnerInput implements ToWarehousePro
 {
     ...
     /** @return array<string, mixed> */
-    public function toArray(): array
+    public function toWarehouseProArray(): array
     {
```

- [ ] **Step 6: Update the transport**

In `src/Http/MicroinvestTransport.php` add the import and change three places:

```php
+use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
```

Line ~116 (docblock of `requestList`):

```php
-     * @param  class-string<T>       $resultClass  must expose static fromArray(array): self
+     * @param  class-string<T&FromWarehousePro>  $resultClass
```

Line ~138:

```php
-            $items[] = $resultClass::fromArray($row);
+            $items[] = $resultClass::fromWarehousePro($row);
```

Line ~170 (in `requestOne`, whose `@param class-string<T> $resultClass` becomes `class-string<T&FromWarehousePro>`):

```php
-        return $resultClass::fromArray($env['data']);
+        return $resultClass::fromWarehousePro($env['data']);
```

- [ ] **Step 7: Update the 7 call sites in resources**

```php
// src/Resources/Items.php:43,48 · src/Resources/Partners.php:43,48
// src/Resources/Payments.php:53 · src/Resources/Documents.php:64
-$input->toArray()
+$input->toWarehouseProArray()

// src/Resources/Operations.php:56
-            static fn (OperationInput $row): array => $row->toArray(),
+            static fn (OperationInput $row): array => $row->toWarehouseProArray(),
```

- [ ] **Step 8: Update the 2 call sites in existing tests**

```php
// tests/Resources/ReadOnlyResourcesTest.php:63,66
-NomenclatureGroupResult::fromArray(
+NomenclatureGroupResult::fromWarehousePro(
```

- [ ] **Step 9: Verify no call site was missed**

Run: `grep -rn 'fromArray(\|->toArray()' src/ tests/`
Expected: no output

- [ ] **Step 10: Run the whole suite**

Run: `composer test`
Expected: PASS — all tests green, including the 3 new ones

- [ ] **Step 11: Commit**

```bash
git add src/Contracts src/Dto src/Http/MicroinvestTransport.php src/Resources tests/Dto tests/Resources/ReadOnlyResourcesTest.php
git commit -m "refactor: name DTO hydration after its wire dialect"
```

---

### Task 3: Преместване на Warehouse Pro в собствено поддърво

Чисто механично: `git mv` + смяна на namespace. **Нула промяна в логиката** — ако някой ред бизнес логика се промени в тази задача, тя е сгрешена.

**Files:**
- Move: `src/Config/MicroinvestConfig.php` → `src/WarehousePro/WarehouseProConfig.php`
- Move: `src/Http/MicroinvestTransport.php` → `src/WarehousePro/WarehouseProTransport.php`
- Move: `src/Resources/*.php` (10 файла) → `src/WarehousePro/Resources/`
- Move: `src/Microinvest.php` → `src/WarehousePro/WarehouseProClient.php`
- Move: `tests/Config/MicroinvestConfigTest.php` → `tests/WarehousePro/WarehouseProConfigTest.php`
- Move: `tests/Http/MicroinvestTransportTest.php` → `tests/WarehousePro/WarehouseProTransportTest.php`
- Move: `tests/Resources/*.php` → `tests/WarehousePro/Resources/`
- Modify: `tests/Pest.php`, `tests/WarehousePro/Resources/CompletenessTest.php`, `src/Laravel/MicroinvestManager.php`, `src/Laravel/Facades/Microinvest.php`
- Unchanged: `src/Exception/`, `src/Http/ResultList.php`, `src/Dto/`, `src/Contracts/`

**Interfaces:**
- Consumes: `FromWarehousePro` / `ToWarehousePro` (Task 2)
- Produces:
  - `Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig` (same constructor: `__construct(string $baseUrl, ?string $apiKey = null, int $timeout = 30)`)
  - `Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport` (same public API: `request`, `requestList`, `requestOne`)
  - `Ux2Dev\Microinvest\WarehousePro\WarehouseProClient` (same 9 resource accessors)
  - `Ux2Dev\Microinvest\WarehousePro\Resources\*`
  - test helper `fakeWarehousePro(FakeHttpClient $http, ?string $apiKey = 'secret-key', string $baseUrl = 'http://127.0.0.1:8700'): WarehouseProClient`

- [ ] **Step 1: Move the files**

```bash
mkdir -p src/WarehousePro/Resources tests/WarehousePro/Resources
git mv src/Config/MicroinvestConfig.php   src/WarehousePro/WarehouseProConfig.php
git mv src/Http/MicroinvestTransport.php  src/WarehousePro/WarehouseProTransport.php
git mv src/Microinvest.php                src/WarehousePro/WarehouseProClient.php
git mv src/Resources/*.php                src/WarehousePro/Resources/
git mv tests/Config/MicroinvestConfigTest.php  tests/WarehousePro/WarehouseProConfigTest.php
git mv tests/Http/MicroinvestTransportTest.php tests/WarehousePro/WarehouseProTransportTest.php
git mv tests/Resources/*.php               tests/WarehousePro/Resources/
rmdir src/Config src/Resources tests/Config tests/Resources
```

- [ ] **Step 2: Rewrite namespaces and class names**

Apply across `src/` and `tests/`:

| Old | New |
|---|---|
| `namespace Ux2Dev\Microinvest\Config;` | `namespace Ux2Dev\Microinvest\WarehousePro;` |
| `namespace Ux2Dev\Microinvest\Http;` (transport file only) | `namespace Ux2Dev\Microinvest\WarehousePro;` |
| `namespace Ux2Dev\Microinvest\Resources;` | `namespace Ux2Dev\Microinvest\WarehousePro\Resources;` |
| `Ux2Dev\Microinvest\Config\MicroinvestConfig` | `Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig` |
| `Ux2Dev\Microinvest\Http\MicroinvestTransport` | `Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport` |
| `Ux2Dev\Microinvest\Resources\` | `Ux2Dev\Microinvest\WarehousePro\Resources\` |
| class `MicroinvestConfig` | class `WarehouseProConfig` |
| class `MicroinvestTransport` | class `WarehouseProTransport` |
| `namespace Ux2Dev\Microinvest;` + `final class Microinvest` (in `WarehouseProClient.php`) | `namespace Ux2Dev\Microinvest\WarehousePro;` + `final class WarehouseProClient` |
| `Ux2Dev\Microinvest\Tests\Config` / `...\Tests\Resources` | `Ux2Dev\Microinvest\Tests\WarehousePro` / `...\Tests\WarehousePro\Resources` |

`src/Http/ResultList.php` keeps `namespace Ux2Dev\Microinvest\Http;` — it is shared and does **not** move. `WarehouseProTransport` and every resource therefore need `use Ux2Dev\Microinvest\Http\ResultList;`.

`WarehouseProConfig`'s error messages stay byte-for-byte identical (`WarehouseProConfigTest` asserts on them).

- [ ] **Step 3: Update the Pest helper**

In `tests/Pest.php`:

```php
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

uses(Ux2Dev\Microinvest\Tests\Laravel\TestCase::class)->in('Laravel');

/**
 * Build a Warehouse Pro client wired to a fake PSR-18 client.
 */
function fakeWarehousePro(
    FakeHttpClient $http,
    ?string $apiKey = 'secret-key',
    string $baseUrl = 'http://127.0.0.1:8700',
): WarehouseProClient {
    $factory = FakeHttpClient::factory();

    return new WarehouseProClient(
        new WarehouseProConfig($baseUrl, $apiKey),
        $http,
        $factory,
        $factory,
    );
}
```

Then replace every `fakeMicroinvest(` with `fakeWarehousePro(` across `tests/`.

- [ ] **Step 4: Fix the reflection-based completeness test**

In `tests/WarehousePro/Resources/CompletenessTest.php`, the glob path is now two levels deeper and the base class moved:

```php
-use Ux2Dev\Microinvest\Resources\Resource;
+use Ux2Dev\Microinvest\WarehousePro\Resources\Resource;
...
-    $files = glob(__DIR__ . '/../../src/Resources/*.php');
+    $files = glob(__DIR__ . '/../../../src/WarehousePro/Resources/*.php');
```

- [ ] **Step 5: Keep the Laravel layer compiling (minimal edit only)**

In `src/Laravel/MicroinvestManager.php` swap the imports and the two type references. Driver support is Task 5 — do **not** add it here.

```php
-use Ux2Dev\Microinvest\Config\MicroinvestConfig;
-use Ux2Dev\Microinvest\Microinvest;
+use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
+use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;
```

```php
-    /** @var array<string, Microinvest> */
+    /** @var array<string, WarehouseProClient> */
     private array $instances = [];
...
-    public function client(): Microinvest
+    public function client(): WarehouseProClient
...
-    private function build(string $connection): Microinvest
+    private function build(string $connection): WarehouseProClient
...
-        $config = new MicroinvestConfig(
+        $config = new WarehouseProConfig(
...
-        return new Microinvest(
+        return new WarehouseProClient(
```

In `src/Laravel/Facades/Microinvest.php` rewrite the `@method` block:

```php
/**
 * @method static MicroinvestManager connection(string $name)
 * @method static string currentConnection()
 * @method static \Ux2Dev\Microinvest\WarehousePro\WarehouseProClient client()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Items items()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Partners partners()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Users users()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Locations locations()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Operations operations()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Store store()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Payments payments()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Documents documents()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\VatGroups vatGroups()
 */
```

- [ ] **Step 6: Verify nothing references the old names**

Run: `grep -rn 'MicroinvestConfig\|MicroinvestTransport\|fakeMicroinvest\|Microinvest\\\\Resources' src/ tests/`
Expected: no output

Run: `composer dump-autoload`
Expected: no PSR-4 warnings

- [ ] **Step 7: Run the whole suite**

Run: `composer test`
Expected: PASS — same number of tests as before Task 3, all green

- [ ] **Step 8: Commit**

```bash
git add -A src tests
git commit -m "refactor: move Warehouse Pro client into its own subtree"
```

---

### Task 4: Контрактите и `each()`

Тук е същинският дизайн: `each()` крие моделите на страниране, които Фаза 2 ще реализира съвсем различно.

**Files:**
- Create: `src/Contracts/PartnerRepository.php`, `src/Contracts/ItemRepository.php`, `src/Contracts/Client.php`
- Modify: `src/WarehousePro/Resources/Resource.php` (константа `EACH_PAGE_SIZE`)
- Modify: `src/WarehousePro/Resources/Partners.php`, `src/WarehousePro/Resources/Items.php` (`implements` + `each()`)
- Modify: `src/WarehousePro/WarehouseProClient.php` (`implements Client` + 6 `list*` метода)
- Test: `tests/WarehousePro/EachPaginationTest.php`, `tests/WarehousePro/ClientContractTest.php`

**Interfaces:**
- Consumes: `WarehouseProClient`, `Partners`, `Items`, `ResultList`, `FakeHttpClient::sequence()`
- Produces:
  - `Contracts\PartnerRepository{ get(int): PartnerResult, create(PartnerInput): PartnerResult, update(int, PartnerInput): PartnerResult, each(): iterable }`
  - `Contracts\ItemRepository{ get(int): ItemResult, create(ItemInput): ItemResult, update(int, ItemInput): ItemResult, each(): iterable }`
  - `Contracts\Client{ partners(): PartnerRepository, items(): ItemRepository, listItemGroups(): ResultList, listPartnerGroups(): ResultList, listTaxGroups(): ResultList, listPaymentTypes(): ResultList, listObjects(): ResultList, listQuantities(?int): ResultList }`

- [ ] **Step 1: Write the failing pagination test**

Create `tests/WarehousePro/EachPaginationTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('walks every page of partners and flattens them', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(
            [['id' => 1], ['id' => 2]],
            headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '3'],
        ),
        FakeHttpClient::jsonResponse(
            [['id' => 3]],
            headers: ['X-CurrentPage' => '2', 'X-TotalPages' => '3'],
        ),
        FakeHttpClient::jsonResponse(
            [['id' => 4]],
            headers: ['X-CurrentPage' => '3', 'X-TotalPages' => '3'],
        ),
    );

    $ids = [];
    foreach (fakeWarehousePro($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([1, 2, 3, 4])
        ->and($http->received)->toHaveCount(3)
        ->and((string) $http->received[0]->getUri())->toContain('page=1')
        ->and((string) $http->received[2]->getUri())->toContain('page=3');
});

it('stops after one page when the API reports no paging headers', function () {
    $http = FakeHttpClient::sequence(FakeHttpClient::jsonResponse([['id' => 1]]));

    $ids = [];
    foreach (fakeWarehousePro($http)->items()->each() as $item) {
        $ids[] = $item->id;
    }

    expect($ids)->toBe([1])
        ->and($http->received)->toHaveCount(1);
});

it('stops on an empty page even if the header claims more', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse([], headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '9']),
    );

    $ids = [];
    foreach (fakeWarehousePro($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([])
        ->and($http->received)->toHaveCount(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/WarehousePro/EachPaginationTest.php`
Expected: FAIL — `Call to undefined method ...Resources\Partners::each()`

- [ ] **Step 3: Create the three contracts**

`src/Contracts/PartnerRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;

/**
 * The partner operations both backends support. Backend-specific filtering
 * and paging stay on the concrete resource classes.
 */
interface PartnerRepository
{
    public function get(int $id): PartnerResult;

    public function create(PartnerInput $input): PartnerResult;

    public function update(int $id, PartnerInput $input): PartnerResult;

    /**
     * Every partner, transparently walking whatever paging model the backend
     * uses. Lazy: rows are fetched one page at a time.
     *
     * @return iterable<PartnerResult>
     */
    public function each(): iterable;
}
```

`src/Contracts/ItemRepository.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;

/**
 * The item operations both backends support. Backend-specific filtering and
 * paging stay on the concrete resource classes.
 */
interface ItemRepository
{
    public function get(int $id): ItemResult;

    public function create(ItemInput $input): ItemResult;

    public function update(int $id, ItemInput $input): ItemResult;

    /**
     * Every item, transparently walking whatever paging model the backend
     * uses. Lazy: rows are fetched one page at a time.
     *
     * @return iterable<ItemResult>
     */
    public function each(): iterable;
}
```

`src/Contracts/Client.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * What both Microinvest backends can do. Anything only one of them supports
 * (users, printable documents, invoices, cost allocation, company data) lives
 * on the concrete client, deliberately outside this interface.
 *
 * Methods prefixed `list` perform a request; `partners()` and `items()` do not.
 */
interface Client
{
    public function partners(): PartnerRepository;

    public function items(): ItemRepository;

    /** @return ResultList<NomenclatureGroupResult> */
    public function listItemGroups(): ResultList;

    /** @return ResultList<NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList;

    /** @return ResultList<VatGroupResult> */
    public function listTaxGroups(): ResultList;

    /** @return ResultList<PaymentTypeResult> */
    public function listPaymentTypes(): ResultList;

    /** @return ResultList<LocationResult> */
    public function listObjects(): ResultList;

    /** @return ResultList<StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList;
}
```

Every import above is used by a `@return` annotation, so none should be flagged as unused.

- [ ] **Step 4: Add the page size constant**

In `src/WarehousePro/Resources/Resource.php`:

```php
abstract class Resource
{
    /** Page size used by the contract-level each() walkers. */
    protected const EACH_PAGE_SIZE = 100;

    public function __construct(protected readonly WarehouseProTransport $transport)
    {
    }
}
```

- [ ] **Step 5: Implement `each()` on Partners**

In `src/WarehousePro/Resources/Partners.php` add the import, the `implements`, and the method:

```php
use Ux2Dev\Microinvest\Contracts\PartnerRepository;
...
final class Partners extends Resource implements PartnerRepository
{
    ...
    /** @return iterable<PartnerResult> */
    public function each(): iterable
    {
        $page = 1;

        while (true) {
            $result = $this->list(page: $page, pageSize: self::EACH_PAGE_SIZE);

            yield from $result->items;

            $totalPages = $result->totalPages;

            if ($totalPages === null || $page >= $totalPages || $result->count() === 0) {
                return;
            }

            $page++;
        }
    }
}
```

- [ ] **Step 6: Implement `each()` on Items**

In `src/WarehousePro/Resources/Items.php`, identical shape against `ItemResult`:

```php
use Ux2Dev\Microinvest\Contracts\ItemRepository;
...
final class Items extends Resource implements ItemRepository
{
    ...
    /** @return iterable<ItemResult> */
    public function each(): iterable
    {
        $page = 1;

        while (true) {
            $result = $this->list(page: $page, pageSize: self::EACH_PAGE_SIZE);

            yield from $result->items;

            $totalPages = $result->totalPages;

            if ($totalPages === null || $page >= $totalPages || $result->count() === 0) {
                return;
            }

            $page++;
        }
    }
}
```

- [ ] **Step 7: Run the pagination test**

Run: `vendor/bin/pest tests/WarehousePro/EachPaginationTest.php`
Expected: PASS (3 tests)

- [ ] **Step 8: Write the failing client contract test**

Create `tests/WarehousePro/ClientContractTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('satisfies the shared Client contract', function () {
    $client = fakeWarehousePro(FakeHttpClient::withJson([]));

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->partners())->toBeInstanceOf(PartnerRepository::class)
        ->and($client->items())->toBeInstanceOf(ItemRepository::class);
});

it('routes each lookup to its Warehouse Pro endpoint', function (string $method, string $path) {
    $http = FakeHttpClient::withJson([]);

    fakeWarehousePro($http)->{$method}();

    expect((string) $http->lastRequest()->getUri())->toContain($path);
})->with([
    ['listItemGroups', '/ItemsGroups'],
    ['listPartnerGroups', '/PartnersGroups'],
    ['listTaxGroups', '/VATGroups'],
    ['listPaymentTypes', '/PaymentTypes'],
    ['listObjects', '/Locations'],
    ['listQuantities', '/Store'],
]);

it('passes the object id through to the store endpoint', function () {
    $http = FakeHttpClient::withJson([]);

    fakeWarehousePro($http)->listQuantities(1232);

    expect((string) $http->lastRequest()->getUri())->toContain('object_id=1232');
});
```

- [ ] **Step 9: Run test to verify it fails**

Run: `vendor/bin/pest tests/WarehousePro/ClientContractTest.php`
Expected: FAIL — `Call to undefined method ...WarehouseProClient::listItemGroups()`

- [ ] **Step 10: Implement the contract on the client**

In `src/WarehousePro/WarehouseProClient.php` add the imports, `implements Client`, and the six lookups after the existing accessors. The existing `partners()` and `items()` already satisfy the contract by covariance — leave them as they are.

```php
use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Http\ResultList;
...
final class WarehouseProClient implements Client
{
    ...
    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult> */
    public function listItemGroups(): ResultList
    {
        return $this->items()->groups();
    }

    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList
    {
        return $this->partners()->groups();
    }

    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult> */
    public function listTaxGroups(): ResultList
    {
        return $this->vatGroups()->list();
    }

    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult> */
    public function listPaymentTypes(): ResultList
    {
        return $this->payments()->types();
    }

    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult> */
    public function listObjects(): ResultList
    {
        return $this->locations()->list();
    }

    /** @return ResultList<\Ux2Dev\Microinvest\Dto\Result\Store\StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList
    {
        return $this->store()->list(objectId: $objectId);
    }
}
```

- [ ] **Step 11: Run the whole suite**

Run: `composer test`
Expected: PASS — all green, including the 4 new contract tests

- [ ] **Step 12: Commit**

```bash
git add src/Contracts src/WarehousePro tests/WarehousePro
git commit -m "feat: add Client/PartnerRepository/ItemRepository contracts with each()"
```

---

### Task 5: Фабрика `Microinvest` и драйвер в Laravel

**Files:**
- Create: `src/Microinvest.php` (ново съдържание — старият файл беше преместен в Task 3)
- Modify: `src/Laravel/MicroinvestManager.php`, `src/Laravel/config/microinvest.php`, `src/Laravel/Facades/Microinvest.php`
- Modify: `README.md`
- Test: `tests/MicroinvestFactoryTest.php`, `tests/Laravel/ManagerTest.php`

**Interfaces:**
- Consumes: `WarehouseProClient`, `WarehouseProConfig`, `Contracts\Client`
- Produces: `Microinvest::warehousePro(WarehouseProConfig, ClientInterface, RequestFactoryInterface, StreamFactoryInterface): WarehouseProClient`; manager connections now carry a `driver` key whose only valid value in Фаза 1 is `warehouse_pro`

- [ ] **Step 1: Write the failing factory test**

Create `tests/MicroinvestFactoryTest.php`:

```php
<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Microinvest;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

it('builds a Warehouse Pro client', function () {
    $factory = FakeHttpClient::factory();

    $client = Microinvest::warehousePro(
        new WarehouseProConfig('http://127.0.0.1:8700', 'k'),
        FakeHttpClient::withJson([]),
        $factory,
        $factory,
    );

    expect($client)->toBeInstanceOf(WarehouseProClient::class)
        ->and($client)->toBeInstanceOf(Client::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/MicroinvestFactoryTest.php`
Expected: FAIL — `Class "Ux2Dev\Microinvest\Microinvest" not found`

- [ ] **Step 3: Write the factory**

Create `src/Microinvest.php`:

```php
<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

/**
 * Entry point for the SDK. Each backend has its own client; this class only
 * exists so both are discoverable from one place. Constructing the clients
 * directly is equally supported.
 */
final class Microinvest
{
    public static function warehousePro(
        WarehouseProConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ): WarehouseProClient {
        return new WarehouseProClient($config, $httpClient, $requestFactory, $streamFactory);
    }
}
```

The class has no constructor on purpose: a private empty one would be an uncoverable line under the `--min=100` gate.

- [ ] **Step 4: Write the failing driver test**

Append to `tests/Laravel/ManagerTest.php`:

```php
it('rejects an unknown driver', function () {
    $manager = new Ux2Dev\Microinvest\Laravel\MicroinvestManager([
        'default' => 'x',
        'connections' => ['x' => ['driver' => 'sap', 'base_url' => 'http://h']],
    ]);

    expect(fn () => $manager->client())
        ->toThrow(Ux2Dev\Microinvest\Exception\ConfigurationException::class, 'Unknown Microinvest driver "sap"');
});

it('defaults to the warehouse_pro driver when none is configured', function () {
    $manager = new Ux2Dev\Microinvest\Laravel\MicroinvestManager([
        'default' => 'x',
        'connections' => ['x' => ['base_url' => 'http://h']],
    ]);

    expect($manager->client())->toBeInstanceOf(Ux2Dev\Microinvest\Contracts\Client::class);
});
```

- [ ] **Step 5: Run test to verify it fails**

Run: `vendor/bin/pest tests/Laravel/ManagerTest.php`
Expected: FAIL — no exception thrown for the unknown driver

- [ ] **Step 6: Add driver dispatch to the manager**

In `src/Laravel/MicroinvestManager.php` change the property, `client()` and `build()` to speak in terms of the contract, and split the Warehouse Pro construction into its own method:

```php
use Ux2Dev\Microinvest\Contracts\Client;
...
    /** @var array<string, Client> */
    private array $instances = [];
...
    public function client(): Client
    {
        return $this->instances[$this->currentConnection] ??= $this->build($this->currentConnection);
    }

    private function build(string $connection): Client
    {
        $connections = (array) ($this->config['connections'] ?? []);

        if (! isset($connections[$connection]) || ! is_array($connections[$connection])) {
            throw new ConfigurationException("Microinvest connection \"{$connection}\" is not configured");
        }

        $c = $connections[$connection];
        $driver = (string) ($c['driver'] ?? 'warehouse_pro');

        return match ($driver) {
            'warehouse_pro' => $this->buildWarehousePro($c),
            default => throw new ConfigurationException("Unknown Microinvest driver \"{$driver}\""),
        };
    }

    /** @param array<string, mixed> $c */
    private function buildWarehousePro(array $c): WarehouseProClient
    {
        $apiKey = $c['api_key'] ?? null;

        $config = new WarehouseProConfig(
            baseUrl: (string) ($c['base_url'] ?? ''),
            apiKey:  $apiKey !== null && $apiKey !== '' ? (string) $apiKey : null,
            timeout: (int) ($c['timeout'] ?? 30),
        );

        $factory = new HttpFactory();

        return new WarehouseProClient(
            $config,
            $this->httpClient ?? new Client(['timeout' => $config->timeout]),
            $this->requestFactory ?? $factory,
            $this->streamFactory ?? $factory,
        );
    }
```

**Name clash:** `GuzzleHttp\Client` and `Contracts\Client` cannot both be imported unaliased. Import the contract as `Client` and refer to Guzzle fully qualified — replace `new Client([...])` with `new \GuzzleHttp\Client([...])` and delete the `use GuzzleHttp\Client;` line.

- [ ] **Step 7: Add the driver key to the shipped config**

`src/Laravel/config/microinvest.php`:

```php
<?php

return [
    'default' => env('MICROINVEST_CONNECTION', 'local'),
    'connections' => [
        'local' => [
            'driver'   => 'warehouse_pro',
            'base_url' => env('MICROINVEST_BASE_URL', 'http://127.0.0.1:8700'),
            'api_key'  => env('MICROINVEST_API_KEY'),
            'timeout'  => (int) env('MICROINVEST_TIMEOUT', 30),
        ],
    ],
];
```

- [ ] **Step 8: Point the facade at the contract**

In `src/Laravel/Facades/Microinvest.php`:

```php
- * @method static \Ux2Dev\Microinvest\WarehousePro\WarehouseProClient client()
+ * @method static \Ux2Dev\Microinvest\Contracts\Client client()
```

- [ ] **Step 9: Update the README**

`README.md` needs these exact edits:

- **L32–33** — imports in the quick-start block:
  ```php
  -use Ux2Dev\Microinvest\Config\MicroinvestConfig;
  -use Ux2Dev\Microinvest\Microinvest;
  +use Ux2Dev\Microinvest\Microinvest;
  +use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;
  ```
- **L35** — `$config = new MicroinvestConfig(` → `$config = new WarehouseProConfig(`
- **L41**:
  ```php
  -$microinvest = new Microinvest($config, new Client(), $factory, $factory);
  +$microinvest = Microinvest::warehousePro($config, new Client(), $factory, $factory);
  ```
- **L74, L76, L79** — the `### MicroinvestConfig` heading and its prose/example: rename the class to `WarehouseProConfig`.
- **L102** — add `'driver' => 'warehouse_pro',` as the first key of the `local` connection.
- **L130–135** — the architecture table:

  | Layer | Class | Note |
  |---|---|---|
  | Config | `Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig` | Base URL + optional API key, validated |
  | Transport | `Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport` | PSR-18 dispatch, query/body building, error mapping |
  | Input DTOs | `Ux2Dev\Microinvest\Dto\Input\{Group}\{Entity}Input` | Mutation bodies; `toWarehouseProArray()` emits snake_case wire keys |
  | Result DTOs | `Ux2Dev\Microinvest\Dto\Result\{Group}\{Entity}Result` | Typed rows; `fromWarehousePro()` hydrates responses |
  | Resources | `Ux2Dev\Microinvest\WarehousePro\Resources\{Group}` | One method per API action |
  | Root client | `Ux2Dev\Microinvest\WarehousePro\WarehouseProClient` | Aggregator exposing every resource |
  | Contracts | `Ux2Dev\Microinvest\Contracts\*` | What every backend supports |
  | Laravel | `Ux2Dev\Microinvest\Laravel\*` | Service provider + multi-connection manager + facade |

- **L188** — `Invalid MicroinvestConfig input` → `Invalid WarehouseProConfig input`
- **New section after the filters section (~L162)**, titled `### Iterating everything`:

  ````markdown
  `partners()` and `items()` implement `Ux2Dev\Microinvest\Contracts\PartnerRepository` /
  `ItemRepository`, whose `each()` walks the whole nomenclature one page at a time:

  ```php
  foreach ($microinvest->partners()->each() as $partner) {
      echo $partner->company, PHP_EOL;
  }
  ```

  It is a generator — rows are fetched lazily, so memory stays flat regardless of how
  many pages the server reports.
  ````

- [ ] **Step 10: Run the whole suite**

Run: `composer test`
Expected: PASS

- [ ] **Step 11: Commit**

```bash
git add src tests README.md
git commit -m "feat: turn Microinvest into a backend factory and add driver config"
```

---

## Definition of done

- [ ] `composer test` зелен
- [ ] `grep -rn 'fromArray(\|->toArray()\|MicroinvestConfig\|MicroinvestTransport\|fakeMicroinvest' src/ tests/` не връща нищо
- [ ] `src/` съдържа `Contracts/`, `Dto/`, `Exception/`, `Http/ResultList.php`, `Laravel/`, `WarehousePro/`, `Microinvest.php` — и нищо друго
- [ ] CI минава на PHP 8.2, 8.3, 8.4 + coverage job с `--min=100`
- [ ] Нула споменавания на micro.bg в `src/` (това е Фаза 2)

## Какво НЕ влиза във Фаза 1

- `MicroBg/` изобщо (Фаза 2: `RequestSigner`, `Envelope`, `MicroBgTransport`, 9 ресурса, `fromMicroBg()`/`toMicroBgArray()` върху споделените DTO-та)
- Contract conformance dataset, който върти двете реализации (Фаза 2 — има смисъл едва когато има втора реализация)
- `micro_bg` драйвер в Laravel manager-а (Фаза 3)
- Полетата, специфични за micro.bg, в споделените DTO-та (Фаза 2)
