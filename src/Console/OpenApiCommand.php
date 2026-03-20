<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Rest\ApiDefinitionManager;
use Directive\Rest\Method;
use Directive\Rest\VersionStatus;
use Directive\Service\Configuration\ConfigurationInterface;
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

        /** @var ConfigurationInterface $config */
        $config = $this->container->get(ConfigurationInterface::class);

        /** @var ApiDefinitionManager $manager */
        $manager = $this->container->get(ApiDefinitionManager::class);

        $appName    = (string) $config->get('app.name', 'Directive');
        $appVersion = (string) $config->get('app.version', '1.0.0');
        $appDesc    = (string) $config->get('app.description', '');

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
            'openapi'    => '3.0.3',
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

        // Map profiles to security requirements.
        // Empty profiles = open (no authentication required).
        $security = [];
        if ($method->profiles !== []) {
            $security = [['bearerAuth' => $method->profiles]];
        }

        $op = [
            'operationId' => $operationId,
            'tags'        => [$tag],
            'summary'     => '',
            'security'    => $security,
            'responses'   => [
                '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['type' => 'object']]]],
                '400' => ['description' => 'Bad Request'],
                '401' => ['description' => 'Unauthorized'],
                '403' => ['description' => 'Forbidden'],
                '409' => ['description' => 'Conflict'],
                '500' => ['description' => 'Internal Server Error'],
            ],
        ];

        // Best-effort: annotate with handler class references
        $op['x-policy'] = $method->policyClass;
        $op['x-api']    = $method->apiClass;

        if ($method->requestEntityClass !== null) {
            $op['x-request-entity'] = $method->requestEntityClass;
        }

        if ($method->responseEntityClass !== null) {
            $op['x-response-entity'] = $method->responseEntityClass;
        }

        return $op;
    }
}
