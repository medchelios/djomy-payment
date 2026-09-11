# Djomy Payment Platform API - Package PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)
[![Total Downloads](https://img.shields.io/packagist/dt/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)
[![License](https://img.shields.io/packagist/l/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)

📦 **Packagist :** [tmoh/djomy-payment](https://packagist.org/packages/tmoh/djomy-payment)

Package PHP **agnostique framework** pour l'intégration de la **Djomy Payment Platform API (v1.0)**.

Compatible avec :

- **Laravel**: versions **10, 11, 12 et 13**
- **Symfony**: via le conteneur de services ou directement le client
- **PHP pur**: aucun framework requis (Guzzle suffit)

## Fonctionnalités

- Génération de la signature **HMAC-SHA256** (en-tête `X-API-KEY: <clientId>:<signature>`)
- Récupération de l'**access token de type Bearer**
- **Lien de paiement** : récupération par référence
- Génération et consultation des liens de paiement
- Mapping des erreurs HTTP en exceptions (400, 401, 403, 404, 429, 5xx)

## Installation

```bash
composer require tmoh/djomy-payment
```

## Configuration

Chaque entreprise reçoit une **clé API** (`clientId`) et une **clé secrète** (`clientSecret`) via l'espace marchand, utilisées pour générer la signature HMAC et demander un access token Bearer.

### Laravel

```bash
php artisan vendor:publish --provider="Tmoh\DjomyPayment\DjomyServiceProvider" --tag="config"
```

```env
DJOMY_BASE_URL=https://sandbox-api.djomy.africa
DJOMY_CLIENT_ID=your_client_id
DJOMY_CLIENT_SECRET=your_client_secret
DJOMY_TIMEOUT=30
DJOMY_AUTO_AUTHENTICATE=false
```


### PHP pur / Symfony

```php
use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\DjomyService;

$client = new DjomyClient('https://api.djomy.com', 'your_client_id', 'your_client_secret');
$djomy = new DjomyService($client);
```

## Authentification

En-tête `X-API-KEY` obligatoire sur chaque requête :

```
X-API-KEY: <clientId>:<signature-hmac>
```

avec `signature-hmac = hash_hmac('sha256', clientId, clientSecret)` :

```php
use Tmoh\DjomyPayment\Signature;

Signature::apiKeyHeaderValue('your_client_id', 'your_client_secret');
// => "your_client_id:1f4d2f...b3a9c0"
```

Access token Bearer :

```php
$token = $client->authenticate();        // retourne et stocke le token
$client->setBearerToken('existing-token'); // ou fournir un token existant
```

L'option `DJOMY_AUTO_AUTHENTICATE=true` déclenche l'authentification automatiquement avant la première requête.

## Utilisation

### Laravel

```php
use Tmoh\DjomyPayment\Facades\Djomy;

try {
    $token = Djomy::authenticate();
    $paymentLink = Djomy::getPaymentLink('PL-2024-0001');
} catch (Tmoh\DjomyPayment\Exceptions\NotFoundException $e) {
    // 404 : lien de paiement introuvable
} catch (Tmoh\DjomyPayment\Exceptions\DjomyException $e) {
    // autre erreur API
}
```

### PHP pur / Symfony

```php
$client->authenticate();
$paymentLink = $service->getPaymentLink('PL-2024-0001');
```

### Générer un lien de paiement

```php
$paymentLink = $service->generatePaymentLink([
    'amountToPay' => 15000,
    'linkName' => 'Order PL-2024-0001',
    'phoneNumber' => '00224623707722',
    'sendSms' => false,
    'description' => 'Payment for order PL-2024-0001',
    'countryCode' => 'GN',
    'usageType' => 'UNIQUE',
    'expiresAt' => '2026-12-31T23:59:59Z',
    'merchantReference' => 'PL-2024-0001',
    'usageLimit' => 1,
    'returnUrl' => 'https://example.com/payments/success',
    'cancelUrl' => 'https://example.com/payments/cancel',
    'allowedPaymentMethods' => ['OM', 'MOMO', 'CARD'],
    'metadata' => [
        'order_id' => 'ORD-456',
    ],
    'skipDjomyStatusPage' => false,
]);
```

The endpoint used is `POST /v1/links`. Optional `customFields` can be provided
with `label`, `placeholder`, and `required` values.

### Demander un paiement direct

Use `initiateDirectPayment()` to call `POST /v1/payments` without redirecting
the payer to the Djomy portal. It supports `OM`, `MOMO`, and `KULU` payment
methods. Card payments must use the portal-payment endpoint instead.

```php
$payment = $service->initiateDirectPayment([
    'paymentMethod' => 'OM',
    'payerIdentifier' => '00224623707722',
    'amount' => 15000,
    'countryCode' => 'GN',
    'description' => 'Payment for order ORD-456',
    'merchantPaymentReference' => 'ORD-456',
    'returnUrl' => 'https://example.com/payments/success',
    'cancelUrl' => 'https://example.com/payments/cancel',
    'metadata' => [
        'order_id' => 'ORD-456',
        'vip' => true,
    ],
]);
```

## Gestion des erreurs

Toutes les exceptions héritent de `DjomyException` :

| Statut | Exception |
|---|---|
| 400 | `BadRequestException` |
| 401 | `UnauthorizedException` |
| 403 | `ForbiddenException` |
| 404 | `NotFoundException` |
| 422 | `UnprocessableEntityException` |
| 429 | `TooManyRequestsException` |
| 500+ | `ServerException` |
| N/A | `AuthenticationException` |
| N/A | `ConnectionException` (réseau : DNS, timeout, connexion refusée) |

## Tests

```bash
composer install
composer test
```

La CI vérifie le package avec plusieurs versions de Laravel, Testbench et Pest. Le code de la librairie reste framework-agnostic et ne nécessite que PHP 8.1+ et Guzzle.

### Qualité du code

```bash
composer format
composer format-check
composer analyse
```

## Licence

MIT
