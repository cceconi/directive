<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

/**
 * Default implementation of RateLimitConfigInterface backed by environment variables.
 *
 * All variables are optional with sane defaults suitable for production use.
 *
 * Environment variables:
 *   RATE_LIMIT_REDIS_DSN    string  default: 'tcp://127.0.0.1:6379'
 *   RATE_LIMIT_WINDOW       int     default: 60  (seconds)
 *   RATE_LIMIT_MAX_REQUESTS int     default: 100
 *   RATE_LIMIT_KEY_TYPE     string  default: 'ip'  (ip|user_id|api_key)
 */
class DefaultRateLimitConfig implements RateLimitConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('RATE_LIMIT_REDIS_DSN', 'tcp://127.0.0.1:6379', 'string');
        $config->optional('RATE_LIMIT_WINDOW', 60, 'int');
        $config->optional('RATE_LIMIT_MAX_REQUESTS', 100, 'int');
        $config->optional('RATE_LIMIT_KEY_TYPE', 'ip', 'string', ['ip', 'user_id', 'api_key']);
    }

    public function getRedisDsn(): string
    {
        return (string) $this->config->get('RATE_LIMIT_REDIS_DSN');
    }

    public function getDefaultWindow(): int
    {
        return (int) $this->config->get('RATE_LIMIT_WINDOW');
    }

    public function getDefaultMaxRequests(): int
    {
        return (int) $this->config->get('RATE_LIMIT_MAX_REQUESTS');
    }

    public function getDefaultKeyType(): RateLimitKeyType
    {
        $raw = (string) $this->config->get('RATE_LIMIT_KEY_TYPE');
        return RateLimitKeyType::from($raw);
    }
}
