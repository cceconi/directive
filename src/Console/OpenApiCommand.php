<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Method;
use Directive\Http\Routing\VersionStatus;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Http\Validator\QueryParametersValidator;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates an OpenAPI 3.0 document from the live API tree.
 *
 * Usage:
 *   php bin/apisy app:openapi
 *   php bin/apisy app:openapi --output /path/to/openapi.yaml
 *
 * Tree introspection is best-effort: request/response schemas are annotated
 * with `x-policy` and `x-response-entity` extension fields rather than
 * fully resolved JSON Schemas.
 */
#[AsCommand(
    name: 'app:openapi',
    description: 'Generate an OpenAPI 3.0 document from the API tree.',
)]
final class OpenApiCommand extends DirectiveCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output file path',
            'openapi.yaml',
        );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $outFile = (string) $input->getOption('output');

        /** @var AppIdentityConfigInterface $config */
        $config = $this->container->get(AppIdentityConfigInterface::class);

        /** @var ApiDefinitionManager $manager */
        $manager = $this->container->get(ApiDefinitionManager::class);

        $appName    = $config->getAppName();
        $appVersion = $config->getAppVersion();
        $appDesc    = $config->getAppDescription();

        $doc = $this->buildDocument($manager, $appName, $appVersion, $appDesc);

        $yaml = Yaml::dump($doc, indent: 2, inline: 6, flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        if (file_put_contents($outFile, $yaml) === false) {
            $output->writeln(sprintf('<error>Could not write to %s</error>', $outFile));

            return self::FAILURE;
        }

        $pathCount = count($doc['paths'] ?? []);
        $output->writeln(sprintf('<info>OpenAPI document written to %s (%d paths)</info>', $outFile, $pathCount));

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Document builder
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function buildDocument(
        ApiDefinitionManager $manager,
        string $appName,
        string $appVersion,
        string $appDesc,
    ): array {
        $paths = [];
        $tags  = [];

        foreach ($manager->getDomains() as $domainName => $domain) {
            foreach ($domain->getVersions() as $versionName => $version) {
                $tag = $domainName . '/' . $versionName;

                $tagEntry = ['name' => $tag, 'description' => ''];
                if ($version->status !== VersionStatus::Open) {
                    $tagEntry['description'] = sprintf(
                        'Status: %s. %s',
                        $version->status->value,
                        $version->info,
                    );
                } elseif ($version->info !== '') {
                    $tagEntry['description'] = $version->info;
                }

                $tags[] = $tagEntry;

                foreach ($version->getServices() as $serviceName => $service) {
                    foreach ($service->getResources() as $resourceName => $resource) {
                        $path = sprintf(
                            '/%s/%s/%s/%s',
                            $domainName,
                            $versionName,
                            $serviceName,
                            $resourceName,
                        );

                        $pathItem = [];

                        foreach ($resource->getMethods() as $httpVerb => $method) {
                            $pathItem[strtolower($httpVerb)] = $this->buildOperation(
                                $domainName,
                                $versionName,
                                $serviceName,
                                $resourceName,
                                $tag,
                                $method,
                            );
                        }

                        // OPTIONS is always implicitly available
                        $pathItem['options'] = [
                            'operationId' => implode('.', [$domainName, $versionName, $serviceName, $resourceName, 'OPTIONS']),
                            'tags'        => [$tag],
                            'summary'     => 'CORS preflight',
                            'responses'   => ['204' => ['description' => 'No Content']],
                        ];

                        $paths[$path] = $pathItem;
                    }
                }
            }
        }

        $info = ['title' => $appName, 'version' => $appVersion];
        if ($appDesc !== '') {
            $info['description'] = $appDesc;
        }

        $components = [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
        ];

        return [
            'openapi'    => '3.1.0',
            'info'       => $info,
            'tags'       => $tags,
            'paths'      => $paths ?: new \stdClass(),
            'components' => $components,
        ];
    }

    /**
     * Build a single OpenAPI operation object.
     *
     * @return array<string, mixed>
     */
    private function buildOperation(
        string $domain,
        string $version,
        string $service,
        string $resource,
        string $tag,
        Method $method,
    ): array {
        $operationId = implode('.', [$domain, $version, $service, $resource, $method->httpMethod]);

        // Security: explicit authenticated flag wins; fallback to allowedRoles inference.
        if ($method->authenticated) {
            $security = [['bearerAuth' => []]];
        } elseif ($method->allowedRoles !== []) {
            $security = [['bearerAuth' => $method->allowedRoles]];
        } else {
            $security = [];
        }

        $op = [
            'operationId' => $operationId,
            'tags'        => [$tag],
            'summary'     => '',
            'security'    => $security,
            'responses'   => $this->buildResponses($method),
        ];

        // Request body schema
        if ($method->requestSchema !== null) {
            if (str_contains($method->requestSchema, '/')) {
                $op['requestBody'] = [
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => $method->requestSchema],
                        ],
                    ],
                ];
            } else {
                $op['x-schema-class'] = $method->requestSchema;
            }
        } elseif ($method->requestEntityClass !== null) {
            $op['x-request-entity'] = $method->requestEntityClass;
        }

        // Response schema annotated inline (class-string only — path-based refs live in buildResponses)
        if ($method->responseSchema !== null && !str_contains($method->responseSchema, '/')) {
            $op['x-response-schema-class'] = $method->responseSchema;
        }

        // Query parameters — auto-generated from QueryParametersValidator subclasses
        if (is_a($method->requestValidatorClass, QueryParametersValidator::class, true)) {
            $validatorClass = $method->requestValidatorClass;
            /** @var QueryParametersValidator $validator */
            $validator   = new $validatorClass();
            $queryParams = $validator->getOpenApiParameters();
            if ($queryParams !== []) {
                $op['parameters'] = $queryParams;
            }
        }

        // Retain class references for tooling
        $op['x-policy'] = $method->requestValidatorClass;
        $op['x-api']    = $method->apiClass;

        if ($method->responseEntityClass !== null) {
            $op['x-response-entity'] = $method->responseEntityClass;
        }

        if ($method->rateLimit !== null) {
            $op['x-rate-limit'] = [
                'window'       => $method->rateLimit->window,
                'max_requests' => $method->rateLimit->maxRequests,
                'key_type'     => $method->rateLimit->keyType->value,
            ];
        }

        return $op;
    }

    /**
     * Build the responses map for an operation.
     *
     * If Method::$errorCodes is non-empty → use that list verbatim (+ 200 always).
     * Otherwise → compute intelligent defaults from other Method properties.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildResponses(Method $method): array
    {
        /** @var array<int, string> $httpDescriptions */
        $httpDescriptions = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];

        /** @var array<int, int> $codes */
        $codes = [200];

        if ($method->errorCodes !== []) {
            // Explicit list — used as-is, 500 is always appended
            foreach ($method->errorCodes as $code) {
                $codes[] = $code;
            }
        } else {
            // Intelligent defaults
            if ($method->requestValidatorClass !== NullRequestValidator::class) {
                $codes[] = 400;
            }

            if ($method->authenticated) {
                $codes[] = 401;
            }

            if ($method->allowedRoles !== []) {
                $codes[] = 403;
            }

            if (strtoupper($method->httpMethod) === 'GET') {
                $codes[] = 404;
            }
        }

        // 500 is always emitted (explicit list and smart defaults alike)
        $codes[] = 500;

        /** @var list<int> $codes */
        $codes = array_values(array_unique($codes));
        sort($codes);

        $responseSchemaIsRef = $method->responseSchema !== null && str_contains($method->responseSchema, '/');

        $responses = [];

        foreach ($codes as $code) {
            $description = $httpDescriptions[$code] ?? 'Error';
            $key         = (string) $code;

            if ($code === 200) {
                $schema  = $responseSchemaIsRef
                    ? ['$ref' => $method->responseSchema]
                    : ['type' => 'object'];
                $responses[$key] = [
                    'description' => $description,
                    'content'     => ['application/json' => ['schema' => $schema]],
                ];
            } else {
                $responses[$key] = ['description' => $description];
            }
        }

        /** @var array<string, array<string, mixed>> $typed */
        $typed = $responses;

        return $typed;
    }
}
