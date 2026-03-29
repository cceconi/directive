<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\Configuration\AbstractConfiguration;

/**
 * Default implementation of RateLimitConfigInterface backed by environment variables.
 *
 * All variables are optional with sane defaults suitable for production use.
 *
 * Environment variables:
 *   DIRECTIVE_RATE_LIMIT_REDIS_DSN    string  default: 'tcp://127.0.0.1:6379'
 *   DIRECTIVE_RATE_LIMIT_WINDOW       int     default: 60  (seconds)
 *   DIRECTIVE_RATE_LIMIT_MAX_REQUESTS int     default: 100
 *   DIRECTIVE_RATE_LIMIT_KEY_TYPE     string  default: 'ip'  (ip|user_id|api_key)
 */
class DefaultRateLimitConfig extends AbstractConfiguration implements RateLimitConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_RATE_LIMIT_REDIS_DSN', 'tcp://127.0.0.1:6379', 'string');
        $this->optional('DIRECTIVE_RATE_LIMIT_WINDOW', 60, 'int');
        $this->optional('DIRECTIVE_RATE_LIMIT_MAX_REQUESTS', 100, 'int');
        $this->optional('DIRECTIVE_RATE_LIMIT_KEY_TYPE', 'ip', 'string', ['ip', 'user_id', 'api_key']);
    }

    public function getRedisDsn(): string
    {
        return (string) $this->get('DIRECTIVE_RATE_LIMIT_REDIS_DSN');
    }

    public function getDefaultWindow(): int
    {
        return (int) $this->get('DIRECTIVE_RATE_LIMIT_WINDOW');
    }

    public function getDefaultMaxRequests(): int
    {
        return (int) $this->get('DIRECTIVE_RATE_LIMIT_MAX_REQUESTS');
    }

    public function getDefaultKeyType(): RateLimitKeyType
    {
        $raw = (string) $this->get('DIRECTIVE_RATE_LIMIT_KEY_TYPE');
        return RateLimitKeyType::from($raw);
    }
}
