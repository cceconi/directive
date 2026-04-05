<a href="https://directivephp.com"><img src="https://directivephp.com/assets/logo-full-dark.svg" width="300" alt="Directive"/></a>

[![Packagist Version](https://img.shields.io/packagist/v/cceconi/directive)](https://packagist.org/packages/cceconi/directive)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **🚧 Early-stage project — not production-ready.**
> Directive has not undergone a security audit. The API is unstable and may introduce breaking changes without notice between releases. Use at your own risk in experimental or learning contexts only.

# Directive

Directive is a PHP 8.4 framework for AI-assisted development built around strict hexagonal architecture. It provides zero-ambiguity conventions — from HTTP routing to persistence — so AI agents can write reliable, testable code from specs rather than guesses.

---

## Requirements

- PHP 8.4+
- Extensions: `json`, `mbstring`
- Composer

---

## Installation

### Recommended — Directive CLI

The fastest way to start a project is with [Directive CLI](https://github.com/cceconi/directive-cli):

```bash
# Install directive-cli globally
composer global require cceconi/directive-cli

# Scaffold a new project
directive-cli new my-project
```

The `directive new` command scaffolds the full project structure, generates `composer.json`, wires the required DI bindings, and sets up the AI-assisted spec workflow — ready to run.

> **Docker alternative (recommended)**: Run directive-cli in an isolated container without a global Composer install. See [directive-cli → Docker](https://github.com/cceconi/directive-cli#docker) for the ready-to-use Dockerfile and docker-compose setup.

### Manual

```bash
composer require cceconi/directive
```

Add the PSR-4 autoload mapping in your `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

---

## Bootstrap

Subclass `AbstractWebApplication` and declare `configureContainer()` to wire your DI bindings and `addServices()` to register your APIs:

```php
<?php

// src/MyApplication.php
declare(strict_types=1);

namespace App;

use App\Infrastructure\Http\User\UserApiDefinition;
use App\Security\JwtWebUser;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Service\Business\ErrorInterface;
use Directive\Service\Business\ErrorManager;
use Directive\Service\Security\WebUserInterface;
use Directive\AbstractWebApplication;

final class MyApplication extends AbstractWebApplication
{
    protected function configureContainer(): void
    {
        $this->addDefinitions([
            // Required — no default provided by the framework
            ErrorInterface::class   => ErrorManager::class,
            WebUserInterface::class => JwtWebUser::class, // your JWT implementation

            // Add your own application bindings here
        ]);
    }

    protected function addServices(): void
    {
        parent::addServices();

        $container = $this->getContainer();

        /** @var ApiDefinitionManager $manager */
        $manager = $container->get(ApiDefinitionManager::class);
        $manager->registerDomain((new UserApiDefinition())->createApi($container));
    }
}
```

**Required bindings** — must be declared; the framework provides no default:

| Interface | Binding |
|---|---|
| `Directive\Service\Business\ErrorInterface` | `ErrorManager::class` or your own implementation |
| `Directive\Service\Security\WebUserInterface` | Your JWT implementation (e.g. `JwtWebUser::class`) |

**Auto-bound by default** (optional override):

| Interface | Default |
|---|---|
| `Directive\Application\EventBus\DomainEventBusInterface` | `NullDomainEventBus` — replace to dispatch domain events |
| Config interfaces (`AppIdentityConfigInterface`, `LoggingConfigInterface`, …) | `Default*Config` implementations |

> Persistence adapters (`ConnectionInterface`, `ClientInterface`, `StorageInterface`) are only required when your application uses those seams — bind them to your driver adapter as needed.

---

## Minimal Example

A GET endpoint that returns a user profile:

```php
<?php

// src/Infrastructure/Http/User/UserApiDefinition.php
declare(strict_types=1);

namespace App\Infrastructure\Http\User;

use App\Infrastructure\Http\User\Api\V1\GetUserApi;
use Directive\Http\Endpoint\ApiDefinitionInterface;
use Directive\Http\Routing\ApiTree;
use Directive\Http\Routing\Domain;
use Psr\Container\ContainerInterface;

final class UserApiDefinition implements ApiDefinitionInterface
{
    public function createApi(ContainerInterface $container): Domain
    {
        return ApiTree::domain('users')
            ->version('v1')
                ->service('account')
                    ->resource('profile')
                        ->get(GetUserApi::class);
    }
}
```

```php
<?php

// src/Infrastructure/Http/User/Api/V1/GetUserApi.php
declare(strict_types=1);

namespace App\Infrastructure\Http\User\Api\V1;

use App\Application\User\UseCase\GetUser\GetUserUseCase;
use Directive\Application\Message\ResultInterface;
use Directive\Http\Endpoint\AbstractUseCaseApi;

final class GetUserApi extends AbstractUseCaseApi
{
    protected function execute(): ResultInterface
    {
        /** @var GetUserUseCase $useCase */
        $useCase = $this->container->get(GetUserUseCase::class);

        return $useCase->execute(
            userId: (int) $this->requestEntity->getUrlParam('id'),
            user:   $this->domainUser(),
        );
    }
}
```

> For the Application layer (`AbstractUseCase`, `AbstractCommand`, `AbstractRepository`, roles, domain events), see [`directive/docs/`](docs/) — available in the next release.

---

## Contributing

- **Style**: PSR-12 strict, `declare(strict_types=1)` on every file
- **Static analysis**: PHPStan level 8 — `make directive.stan` must report zero errors
- **Tests**: Pest — `make directive.test` must report zero failures
- **Pull requests**: one PR = one coherent scope; no out-of-scope refactoring

---

## License

MIT
