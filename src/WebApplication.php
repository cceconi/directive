<?php

declare(strict_types=1);

namespace Directive;

use Directive\Service\Configuration\ConfigurationInterface;
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
     * Built-in routes: POST /maintenance, GET /appinfo/{key}
     *
     * The actual dispatch logic lives in Rest\Router (Epic 3).
     */
    private function setRoutes(): void
    {
        $app = $this->slim;

        // -- API routes (all HTTP verbs) ----------------------------------
        $app->any('/{domain}/{version}/{service}/{resource}', function ($request, $response) {
            // Epic 3: delegate to Rest\Router::resolve()
            return $response;
        });

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

        // -- Catch-all 404 ------------------------------------------------
        $app->any('/{routes:.*}', function ($request, $response) {
            return $response->withStatus(404);
        });
    }

    /**
     * Apply the middleware stack.
     *
     * The stack is intentionally minimal at Epic 1.
     * Framework middlewares (Logger, HttpSecurity, Maintenance, etc.)
     * are registered in Epic 6 and driven by Configuration.
     */
    private function applyMiddleware(): void
    {
        $this->slim->addRoutingMiddleware();

        $this->slim->addErrorMiddleware(
            displayErrorDetails: false,
            logErrors: true,
            logErrorDetails: true,
        );

        // Epic 6: add framework middlewares here.
    }

    /**
     * Override to register custom Slim routes that bypass the standard API stack.
     */
    protected function addCustomRoutes(): void {}
}
