<?php

namespace Tarekdj\DockerClient\Docker;

use Nyholm\Psr7\Uri;
use Tarekdj\Docker\ApiClient\Client as DockerApiClient;
use Tarekdj\DockerClient\Exception\InvalidClientException;
use Tarekdj\DockerClient\Http\Client;

class ApiClient extends DockerApiClient
{
    /**
     * @var Client
     */
    protected $httpClient;

    /* @phpstan-ignore-next-line */
    public static function create($httpClient = null, array $additionalPlugins = [], array $additionalNormalizers = []): ApiClient
    {
        if (false === ($httpClient instanceof Client)) {
            throw new InvalidClientException('Invalid client. Use \Tarekdj\DockerClient\Client.');
        }

        return parent::create($httpClient, $additionalPlugins, $additionalNormalizers);
    }

    /**
     * Get Docker host.
     */
    public function getHost(): string
    {
        $remote = $this->httpClient->getConfig()['remote_socket'];
        $schema = explode(':', $remote);

        return match ($schema[0] ?? '') {
            'http', 'https' => (new Uri($remote))->getHost(),
            default => 'localhost',
        };
    }
}
