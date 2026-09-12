# Djomy Payment Platform API - Package PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)
[![Total Downloads](https://img.shields.io/packagist/dt/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)
[![License](https://img.shields.io/packagist/l/tmoh/djomy-payment.svg?style=flat-square)](https://packagist.org/packages/tmoh/djomy-payment)

📦 **Packagist :** [tmoh/djomy-payment](https://packagist.org/packages/tmoh/djomy-payment)

📚 **Documentation officielle :** [developers.djomy.africa](https://developers.djomy.africa/)

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
- Initiation et consultation des paiements
- Vérification et parsing des **webhooks** (signature `X-Webhook-Signature`, payloads V1/V2)
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
DJOMY_WEBHOOK_URL=https://example.com/webhooks/djomy
DJOMY_WEBHOOK_VERSION=v2
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
    'phoneNumber' => '1234567890',
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

### Lister les paiements

```php
$payments = $service->getAllPayments([
    'statuses' => 'SUCCESS,PENDING',
    'startDate' => '2024-01-01',
    'endDate' => '2024-12-31',
    'paginationRequest' => [
        'page' => 0,
        'size' => 20,
    ],
]);
```

The endpoint used is `GET /v1/payments`. Use `getPaymentStatus($transactionId)`
to retrieve a specific transaction status.

### Demander un paiement direct

Use `initiateDirectPayment()` to call `POST /v1/payments` without redirecting
the payer to the Djomy portal. It supports `OM`, `MOMO`, and `KULU` payment
methods. Card payments must use the portal-payment endpoint instead.

```php
$payment = $service->initiateDirectPayment([
    'paymentMethod' => 'OM',
    'payerIdentifier' => '1234567890',
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

### Demander un paiement avec redirection

Use `initiatePortalPayment()` to call `POST /v1/payments/gateway`. The response
contains the Djomy redirect URL; redirect the payer to that URL to complete the
payment. This endpoint supports all payment methods, including `CARD` for Visa
and Mastercard.

```php
$payment = $service->initiatePortalPayment([
    'amount' => 15000,
    'countryCode' => 'GN',
    'payerNumber' => '1234567890',
    'allowedPaymentMethods' => ['OM', 'MOMO', 'CARD'],
    'description' => 'Payment for order ORD-456',
    'merchantPaymentReference' => 'ORD-456',
    'returnUrl' => 'https://example.com/payments/success',
    'cancelUrl' => 'https://example.com/payments/cancel',
    'metadata' => [
        'order_id' => 'ORD-456',
        'vip' => true,
    ],
]);

$redirectUrl = $payment['redirectUrl'];
```

## Webhooks

Les webhooks sont des notifications HTTP `POST` envoyées par Djomy à **votre** endpoint (à configurer dans l'espace développeur Djomy, voir `DJOMY_WEBHOOK_URL`). Chaque payload est signé : la signature HMAC-SHA256 est placée dans l'en-tête `X-Webhook-Signature` au format `v1:<signature>`.

### Vérifier et parser un webhook

`Tmoh\DjomyPayment\Webhooks` vérifie la signature à partir du **body brut** (jamais du body re-décodé) puis renvoie un `WebhookEvent`.

```php
use Tmoh\DjomyPayment\Facades\DjomyWebhook;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

try {
    $event = DjomyWebhook::parse($rawBody, $signature);
} catch (Tmoh\DjomyPayment\Exceptions\DjomyException $e) {
    // signature invalide ou body non JSON : ignorer le webhook
    abort(400);
}

$event->eventType();              // ex. "payment.success"
$event->eventId();                // UUID de l'événement
$event->paymentLinkReference();   // référence du lien associé
$event->timestamp();              // date de l'événement
$event->payment();                // données du paiement (V2)
$event->payout();                 // données du transfert (V2)
```

### Versions du payload

L'enveloppe (`message`, `eventType`, `eventId`, `data`, `timestamp`) est identique en V1 et V2 ; seule la structure de `data` change. La version est portée par la configuration webhook (`DJOMY_WEBHOOK_VERSION`, `v2` par défaut).

```php
$event->payment();                                  // V2 : data.payment
$event->data(Tmoh\DjomyPayment\Webhooks\WebhookEvent::VERSION_V1); // V1 : data directement
```

En V2, un seul des deux objets `data.payment` ou `data.payout` est présent selon l'événement.

### Types d'événements

`payment.created`, `payment.redirected`, `payment.pending`, `payment.cancelled`, `payment.timeout`, `payment.success`, `payment.failed`, `payment.refunded`, `payout.success`, `payout.failed`.

Des constantes sont disponibles : `WebhookEvent::PAYMENT_SUCCESS`, `WebhookEvent::PAYOUT_FAILED`, etc.

### Vérification manuelle

```php
use Tmoh\DjomyPayment\Signature;

$isValid = Signature::verifyWebhook($rawBody, $signatureHeader, $clientSecret);
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
