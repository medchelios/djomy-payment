# Contribuer

Merci de l'intérêt porté à `tmoh/djomy-payment` ! Ce guide décrit le processus pour proposer une contribution (correctif, fonctionnalité, documentation).

## Prérequis

- PHP **8.1+**
- [Composer](https://getcomposer.org/)

## Installation

```bash
git clone https://github.com/medchelios/djomy-payment.git
cd djomy-payment
composer install
```

## Signaler un problème

Avant d'ouvrir une issue :

1. Vérifie que le problème n'est pas déjà signalé dans les issues existantes.
2. Fournis un titre clair et une description détaillée.
3. Ajoute un exemple reproductible (version de PHP, version de Laravel le cas échéant, code minimal, comportement attendu vs observé).

## Proposer une contribution

### 1. Créer une branche

```bash
git checkout -b feat/ma-fonctionnalite
```

Convention de nommage suggérée :

| Préfixe | Usage |
|---|---|
| `feat/` | nouvelle fonctionnalité |
| `fix/` | correction de bug |
| `docs/` | documentation |
| `refactor/` | refactoring sans changement de comportement |
| `test/` | ajout ou correction de tests |

### 2. Respecter les conventions de code

- Le code suit **PSR-12** (formaté par [Laravel Pint](https://laravel.com/docs/pint)).
- Le package reste **framework-agnostic** : PHP 8.1+ et Guzzle uniquement.
- Les nouvelles fonctionnalités exposées par l'API Djomy doivent suivre le pattern existant :
  - une **Endpoint** (`src/Endpoints/DjomyEndpoints.php`),
  - une **Action** (`src/Actions/`),
  - une méthode dans `DjomyService`.

### 3. Écrire des tests

Toute contribution doit être couverte par des tests [Pest](https://pestphp.com/) dans `tests/`.

```bash
composer test
```

### 4. Vérifier la qualité du code

```bash
composer format        # applique le formatage
composer format-check  # vérifie le formatage sans modifier
composer analyse       # analyse statique (PHPStan)
```

Tout doit passer avant d'ouvrir une Pull Request.

### 5. Commits

Les messages de commit doivent suivre les [Conventional Commits](https://www.conventionalcommits.org/). C'est **obligatoire** : ils déterminent automatiquement la prochaine version (voir [Releases](#releases)).

```
feat: ajoute la consultation d'un payout
fix: corrige l'encodage de la référence de lien
docs: complète la section webhooks
```

| Commit | Effet sur la version |
|---|---|
| `fix:` | patch (`1.1.x`) |
| `feat:` | minor (`1.x.0`) |
| `feat!:` / `BREAKING CHANGE:` | major (`x.0.0`) |

### 6. Ouvrir une Pull Request

- Pousse ta branche et ouvre une PR vers `main`.
- Décris clairement le changement et référence l'issue associée (`Closes #123`).
- Vérifie que la CI (tests, Pint, PHPStan) est verte.
- Une fois la PR mergée, aucune action manuelle n'est nécessaire : la release est automatisée.

## Releases

Les versions sont gérées automatiquement par [release-please](https://github.com/googleapis/release-please) à partir des commits sur `main` :

1. Un push sur `main` crée ou met à jour une **PR de release** (bump de version + `CHANGELOG.md`).
2. Le merge de cette PR crée le tag `vX.Y.Z` et la GitHub Release.
3. Packagist récupère la nouvelle version via son webhook.

Ne modifie donc **jamais** manuellement la version dans `composer.json` ni le tag : release-please s'en charge.

## Licence

En contribuant, tu acceptes que ta contribution soit publiée sous la licence [MIT](LICENSE) du projet.
