# Дизайн: `ux2dev/microinvest` с два backend-а - Warehouse Pro и micro.bg

**Дата:** 2026-07-23
**Статус:** приет, предстои план за имплементация

## 1. Контекст

`ux2dev/microinvest` днес е SDK само за **Microinvest Warehouse Pro REST API** (Utility Center, порт 8700/8701). Появи се нуждата да се поддържа и **micro.bg** - онлайн (SaaS) версията на Микроинвест, чието „External App API" е описано в `Api_v1.4.pdf` (34 стр., в корена на репото).

Двете API-та стоят върху **един и същ домейн модел** (таблици `partners`, `goods`; `PriceOut1..10`, `PriceGroup`, `TaxGroup`, `Deleted` присъстват и в двете), но се разминават непримиримо по жицата.

### Разлики по слоеве

| | Warehouse Pro | micro.bg |
|---|---|---|
| Стил | REST - `GET /Partners`, `PUT /Partner?id=…` | RPC - един endpoint `https://micro.bg/ExtApps/ExternalApp/API/`, винаги `POST` |
| Тяло | JSON body | form-encoded, точно 2 полета: `ApiId`, `Request` |
| Кодиране | `json_encode` | `urlencode(base64_encode(json_encode($r)))` + долепен `hash_hmac('sha256', $encoded, $secret)` |
| Auth | опционален `X-API-Key` header | `ApiId` + `SecretKey`; ключът не пътува, служи за подпис |
| Грешки | HTTP статус 4xx/5xx | винаги HTTP 200 + обвивка `{status, errors[], data}` |
| Страниране | `page`/`page_size` + `X-CurrentPage`/`X-TotalPages` | `fromId` + `limit` (курсор) |
| Имена по жицата | snake_case (`group_id`, `price_out1`) | PascalCase (`GroupId`, `PriceOut1`, `eMail`) |

### Решение

**Един пакет, споделено ядро, две реализации като отделни поддървета в `src/`, с тесни интерфейси само върху реалното пресечно множество** (разгледани и отхвърлени: пълна независимост без интерфейси; пълна полиморфия с драйвер, която изисква `UnsupportedOperationException`).

Пакетът няма нито един git таг → не е пускан → преместването на namespace-и е без BC цена.

## 2. Структура на `src/`

```
src/
├── Contracts/
│   ├── Client.php
│   ├── PartnerRepository.php
│   ├── ItemRepository.php
│   └── Dto/
│       ├── FromWarehousePro.php
│       ├── FromMicroBg.php
│       ├── ToWarehousePro.php
│       └── ToMicroBg.php
│
├── Dto/                            ← споделени между двата backend-а
│   ├── Input/{Partners,Items,Operations,Payments,Documents}/…
│   └── Result/{Partners,Items,Operations,Payments,Locations,Store,…}/…
│
├── Exception/                      ← непроменен, споделен
├── Http/ResultList.php             ← непроменен, споделен
│
├── WarehousePro/                   ← днешният код, преместен 1:1 (git mv + namespace)
│   ├── WarehouseProConfig.php
│   ├── WarehouseProTransport.php
│   ├── WarehouseProClient.php
│   └── Resources/{Items,Partners,Users,Locations,Operations,Store,Payments,Documents,VatGroups}.php
│
├── MicroBg/
│   ├── MicroBgConfig.php
│   ├── RequestSigner.php
│   ├── Envelope.php
│   ├── MicroBgTransport.php
│   ├── MicroBgClient.php
│   └── Resources/{Items,Partners,Groups,Operations,Payments,Objects,TaxGroups,Company,Invoices}.php
│
├── Laravel/
└── Microinvest.php                 ← статична фабрика
```

### Решения по именуване

- **По продуктите, не по метафори:** `WarehousePro` / `MicroBg`, а не `Local` / `Cloud`. Клиентът разпознава „Склад Pro" и „micro.bg".
- **`Microinvest.php` от клиент става фабрика:** `Microinvest::warehousePro(...)` / `Microinvest::microBg(...)`. Директното `new WarehouseProClient(...)` остава легитимно.
- **`RequestSigner` и `Envelope` са отделни от транспорта** - двете места, където micro.bg може да се счупи тихо, се тестват като чист unit без HTTP.

## 3. Контракти

### 3.1 Repository интерфейси

```php
interface PartnerRepository
{
    public function get(int $id): PartnerResult;
    public function create(PartnerInput $input): PartnerResult;
    public function update(int $id, PartnerInput $input): PartnerResult;

    /** @return iterable<PartnerResult> */
    public function each(): iterable;
}
```

`ItemRepository` е симетричен с `ItemResult`/`ItemInput`.

**`each()` е генератор и скрива страницирането** - единственото непримиримо различие:

- `WarehousePro` върви по страници до `X-TotalPages`;
- `MicroBg` върви по `fromId` с `limit`, докато не върне по-малко записа от заявените.

Пълните филтри (`page`, `page_size`, `company`, `fromDate`, `Barcode`, `Phone`, …) остават на конкретните ресурсни класове с днешните named-args сигнатури.

**Защо `list()` не е в контракта:** пресечното множество на филтрите е само `code`. Дори `vat_id` ↔ `TaxNo` е капан - WHP филтрира по ДДС номер, micro.bg `TaxNo` е ЕИК (`TaxID`), тоест различни колони.

### 3.2 `Contracts\Client`

```php
interface Client
{
    public function partners(): PartnerRepository;
    public function items(): ItemRepository;

    public function listItemGroups(): ResultList;
    public function listPartnerGroups(): ResultList;
    public function listTaxGroups(): ResultList;
    public function listPaymentTypes(): ResultList;
    public function listObjects(): ResultList;
    public function listQuantities(?int $objectId = null): ResultList;
}
```

Глаголът `list*` е нарочен: тези методи правят HTTP заявка. `partners()` не прави - връща repository.

### 3.3 Съзнателно извън контрактите

| | Къде живее | Защо |
|---|---|---|
| `delete()` | само `MicroBgClient` | не е потвърдено, че WHP REST го поддържа; SDK-ът не го имплементира днес |
| Операции (продажби) | и двата, различни подписи | `saveOperation` е upsert по `ExtAppDocId`; WHP `create(array $rows)` е друга форма |
| Плащания | и двата, различни | WHP: `list`/`get`/`create`; micro.bg: само `addPayment` |
| `users()`, `documents()` | само `WarehouseProClient` | няма ги в micro.bg |
| `createInvoice()`, `createCostAllocation()`, `getCompanyData()`, `getBankAccounts()` | само `MicroBgClient` | няма ги в WHP |

**Изпращането на продажба няма да е полиморфно.** Общ `OperationInput` би скрил `ExtAppDocId` - най-ценното в micro.bg (идемпотентност при ре-синхронизация). Преоценява се, след като видим реалното поведение на API-то.

## 4. DTO механика

Едно DTO на същност, обединение (union) на полетата, два named constructor-а:

```php
final class PartnerResult implements FromWarehousePro, FromMicroBg
{
    public function __construct(
        // общо ядро (16): id, code, company, mol, city, address, phone, email,
        //                 taxId, vatId, priceGroup, discount, type, groupId,
        //                 deleted, cardNumber
        // само WHP:       company2, mol2, city2, address2, phone2, fax,
        //                 bank*, isVeryUsed, userId, userRealTime, note1, note2, paymentDays
        // само micro.bg:  contactPerson, partnerNote, groupName, groupPath, dateUpdated
    ) {}

    public static function fromWarehousePro(array $d): static { /* snake_case */ }
    public static function fromMicroBg(array $d): static { /* PascalCase */ }
}
```

Всички полета остават nullable с защитни касти - така е и днес, тоест обединението не въвежда нов компромис.

Транспортите се типизират по интерфейса:

```php
/** @param class-string<FromWarehousePro> $resultClass */
public function requestList(string $method, string $path, array $query, string $resultClass): ResultList
```

`UserResult` имплементира само `FromWarehousePro`; `CompanyResult` - само `FromMicroBg`. Подаването на грешната двойка транспорт↔DTO се хваща от PHPStan.

Симетрично за вход: `ToWarehousePro::toWarehouseProArray()` / `ToMicroBg::toMicroBgArray()`.

**Приет компромис:** dialect-специфични полета във входните DTO-та се игнорират мълчаливо от другия диалект (напр. `bankName` при micro.bg). Алтернативата - изключение - прави споделените Input DTO-та безполезни. Отбелязва се в PHPDoc на всяко такова поле.

## 5. Обработка на грешки

`Exception/` не се променя. `ApiException` вече носи `httpStatus`, `apiCode`, `apiMessage`, `body`.

| | Warehouse Pro | micro.bg |
|---|---|---|
| Мрежов проблем | `TransportException` | `TransportException` |
| Невалиден JSON | `InvalidResponseException` | `InvalidResponseException` |
| Отказ от API | HTTP 4xx/5xx → `ApiException` | HTTP 200 + `status: false` → `ApiException` |

За micro.bg `Envelope` слепва `errors[]` в `apiMessage`, а пълната обвивка отива в `body` (`$e->body['errors']` дава суровия масив). `httpStatus` остава 200 - това е честният статус.

`Envelope` приема **и `status`, и `success`** като ключ за успех: описанието на `class_ApiMicroBg` в PDF-а говори за `success`, а всички примери връщат `status`.

## 6. Laravel

```php
'connections' => [
    'sklad' => [
        'driver'   => 'warehouse_pro',
        'base_url' => env('MICROINVEST_URL', 'http://127.0.0.1:8700'),
        'api_key'  => env('MICROINVEST_API_KEY'),
        'timeout'  => 30,
    ],
    'online' => [
        'driver'     => 'micro_bg',
        'api_id'     => env('MICROBG_API_ID'),
        'secret_key' => env('MICROBG_SECRET_KEY'),
        'timeout'    => 30,
    ],
],
```

`MicroinvestManager::connection(?string $name = null): Contracts\Client` - immutable clone за смяна на връзка, както е днес. За backend-специфични методи потребителят type-hint-ва конкретния клас и PHP хвърля `TypeError` при грешна връзка. Без `UnsupportedOperationException`.

## 7. Тестове

Pest 4 + съществуващият `FakeHttpClient`, покритие 100% в CI (непроменено).

| Тест | Какво покрива |
|---|---|
| `RequestSignerTest` | редът `json → base64 → urlencode → HMAC`; че подписът е върху **кодирания** низ |
| `EnvelopeTest` | `status` и `success`; `errors[]` → `ApiException`; `data: NULL` при delete |
| Contract conformance | един dataset с двете реализации на `PartnerRepository`/`ItemRepository`, общ набор твърдения |
| `each()` страниране | WHP: 3 страници по `X-TotalPages`; micro.bg: 3 цикъла по `fromId`; еднакъв плосък резултат |
| Completeness (reflection) | всеки документиран micro.bg метод има съответен метод в ресурс |

**Ограничение:** PDF-ът дава примерни `ApiId`/`SecretKey`, но **няма очакван хеш** - нямаме златен вектор. Тестваме реда на операциите и регресия спрямо собствен baseline. Първата реална заявка към micro.bg е истинската проверка.

## 8. Открити въпроси

1. **`getMeasures()` не е документиран.** `insertItem()` изисква `MeasureId` и препраща към „вж метод `getMeasures()`", но такъв метод липсва в 34-те страници. Без него не могат да се създават стоки, освен ако id-тата на мерните единици не са известни предварително. → въпрос към Микроинвест.
2. **Няма достъп до реален micro.bg акаунт.** Нужни са `API Идентификатор` и `Секретен ключ` (Администриране → Връзка с ел. магазини → Регистриране на ново приложение). До тогава пишем изцяло срещу PDF-а.
3. **Несъответствия в PDF-а**, които чакат проверка на живо: `status` vs `success`; при партньори пише `Deleted: 1 - да, 2 - не` (при стоки е `1 - да, 0 - не`); примерни дати `2018-02-31` (несъществуваща).
4. **Поддържа ли Warehouse Pro REST `DELETE`?** Ако да, `delete()` се промотира в контрактите.
5. **Има ли WHP филтър по дата на промяна?** Ако да, `each()` може да получи `?DateTimeImmutable $since` и инкрементална синхронизация да стане полиморфна.
6. **Поведение след изтичане на абонамента** (`PaymentToDate` в `getCompanyData`) - документацията казва, че API-то спира да връща информация; не е ясно дали като грешка или като празен резултат.
