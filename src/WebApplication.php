<?php

declare(strict_types=1);

namespace Directive;

use Directive\Http\Middleware\AuthMiddleware;
use Directive\Http\Middleware\ClientHeaderMiddleware;
use Directive\Http\Middleware\CompressResponseMiddleware;
use Directive\Http\Middleware\CookieMiddleware;
use Directive\Http\Middleware\HttpSecurityMiddleware;
use Directive\Http\Middleware\LoggerMiddleware;
use Directive\Http\Middleware\MaintenanceMiddleware;
use Directive\Http\Middleware\RequestIdMiddleware;
use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Health\HealthManager;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * HTTP entry point.
 *
 * Usage:
 *   (new WebApplication())
 *       ->setConfig(MyConfig::class)
 *       ->run();
 *
 * For tests / process-mode, call resolve(ServerRequestInterface) instead of run().
 */
class WebApplication extends AbstractApplication
{
    /** @var App<\Psr\Container\ContainerInterface|null> */
    private App $slim;

    // ------------------------------------------------------------------
    // ApplicationInterface
    // ------------------------------------------------------------------

    /**
     * Read the incoming request from PHP globals and run the Slim app.
     */
    public function run(): void
    {
        $factory = new Psr17Factory();
        $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);

        $this->slim->run($creator->fromGlobals());
    }

    /**
     * Process a pre-built request and return the response.
     * Useful for tests and embedded/worker contexts.
     */
    public function resolve(ServerRequestInterface $request): ResponseInterface
    {
        return $this->slim->handle($request);
    }

    // ------------------------------------------------------------------
    // Hooks
    // ------------------------------------------------------------------

    protected function runtimeLoggerName(): string
    {
        return 'webapp';
    }

    protected function registerServices(ConfigurationInterface $config): void
    {
        parent::registerServices($config);
        // Web-specific service definitions added in subsequent epics
        // (Router, HttpResponse, WebUser, security services…).
    }

    protected function addServices(): void
    {
        parent::addServices();
        $this->bootSlim();
    }

    // ------------------------------------------------------------------
    // Slim bootstrap
    // ------------------------------------------------------------------

    private function bootSlim(): void
    {
        AppFactory::setContainer($this->getContainer());
        $this->slim = AppFactory::create();

        $this->setRoutes();
        $this->addCustomRoutes();
        $this->applyMiddleware();
    }

    /**
     * Register all framework routes.
     *
     * Route pattern: /{domain}/{version}/{service}/{resource}
     * Built-in routes: POST /maintenance, GET /appinfo/{key},
     *                  GET /health/live, GET /health/ready
     *
     * The actual dispatch logic lives in Rest\Router (Epic 3).
     */
    private function setRoutes(): void
    {
        $app       = $this->slim;
        $container = $this->getContainer();
        $checks    = $this->configureHealthChecks();

        // -- API routes (non-OPTIONS HTTP verbs) --------------------------
        // OPTIONS is handled separately to avoid a FastRoute duplicate conflict.
        $app->map(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            '/{domain}/{version}/{service}/{resource}',
            function ($request, $response) {
                // Epic 3: delegate to Rest\Router::resolve()
                return $response;
            }
        );

        // -- CORS OPTIONS preflight ---------------------------------------
        $app->options('/{domain}/{version}/{service}/{resource}', function ($request, $response) {
            // Epic 3: delegate to Rest\Router::resolveOptions()
            return $response;
        });

        // -- Built-in: maintenance ----------------------------------------
        $app->post('/maintenance', function ($request, $response) {
            // Epic 8: delegate to MaintenanceManager::resolve()
            return $response;
        });

        // -- Built-in: app info -------------------------------------------
        $app->get('/appinfo/{key}', function ($request, $response) {
            // Epic 8: delegate to AppManager::resolve()
            return $response;
        });

        // -- Built-in: health probes (IETF + Kubernetes) ------------------
        $app->get('/health/live', function ($request, $response) use ($container) {
            /** @var HealthManager $healthManager */
            $healthManager = $container->get(HealthManager::class);
            return $healthManager->resolveLive($response);
        });

        $app->get('/health/ready', function ($request, $response) use ($container, $checks) {
            /** @var HealthManager $healthManager */
            $healthManager = $container->get(HealthManager::class);
            return $healthManager->resolveReady($response, $checks);
        });

        // -- Catch-all 404 ------------------------------------------------
        $app->any('/{routes:.*}', function ($request, $response) {
            return $response->withStatus(404);
        });
    }

    /**
     * Apply the default middleware stack.
     *
     * Slim processes middlewares in LIFO order, so the last middleware added
     * here executes first on the incoming request. The desired execution order
     * (outermost → innermost) is:
     *
     *   1. RequestIdMiddleware       — generate/propagate X-Request-Id
     *   2. LoggerMiddleware          — log version on entry, flush on exit
     *   3. HttpSecurityMiddleware    — CORS + security headers
     *   4. MaintenanceMiddleware     — 503 if maintenance active
     *   5. ClientHeaderMiddleware    — parse X-Client-* headers
     *   6. CompressResponseMiddleware— gzip/deflate response body
     *   7. CookieMiddleware          — load request cookies into container
     *   8. AuthMiddleware            — authenticate user (needs cookies loaded)
     *      ── Slim RoutingMiddleware ──
     *      ── Slim ErrorMiddleware   ──
     *
     * Optional middlewares (BusinessBenchmarkMiddleware, RateLimitMiddleware)
     * are NOT pre-wired. Register them in configureMiddleware() if needed.
     */
    private function applyMiddleware(): void
    {
        // Innermost: Slim built-ins (added first = executed last)
        $this->slim->addRoutingMiddleware();

        $this->slim->addErrorMiddleware(
            displayErrorDetails: false,
            logErrors: true,
            logErrorDetails: true,
        );

        // Default stack — added in reverse execution order (LIFO)
        $this->slim->add(AuthMiddleware::class);               // 8 — executes last (needs cookies)
        $this->slim->add(CookieMiddleware::class);             // 7
        $this->slim->add(CompressResponseMiddleware::class);   // 6
        $this->slim->add(ClientHeaderMiddleware::class);       // 5
        $this->slim->add(MaintenanceMiddleware::class);        // 4 — can short-circuit (503)
        $this->slim->add(HttpSecurityMiddleware::class);       // 3
        $this->slim->add(LoggerMiddleware::class);             // 2
        $this->slim->add(RequestIdMiddleware::class);          // 1 — executes first (outermost)

        // Application-level hook: override to add custom / optional middlewares
        $this->configureMiddleware($this->slim);
    }

    /**
     * Override to register custom or optional middlewares.
     *
     * Called at the end of applyMiddleware(), after the default stack is wired.
     * Use $slim->add() to append middlewares that execute before the default stack
     * (outermost), or use route-specific middleware registration here.
     *
     * Example:
     *   $slim->add(BusinessBenchmarkMiddleware::class);
     *
     * @param App<\Psr\Container\ContainerInterface|null> $slim
     */
    protected function configureMiddleware(App $slim): void {}

    /**
     * Override to register application-level health checks for GET /health/ready.
     *
     * Returns an empty array by default (no application checks).
     * Each entry is a named check: key = check name, value = HealthCheckInterface.
     *
     * Example:
     *   return ['database' => new DatabaseHealthCheck($this->get(PDO::class))];
     *
     * @return array<string, HealthCheckInterface>
     */
    protected function configureHealthChecks(): array
    {
        return [];
    }

    /**
     * Override to register custom Slim routes that bypass the standard API stack.
     */
    protected function addCustomRoutes(): void {}
}
