<?php

namespace Tarekdj\DockerClient;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Tarekdj\Docker\ApiClient\Normalizer\JaneObjectNormalizer;
use Tarekdj\DockerClient\Docker\ApiClient;
use Tarekdj\DockerClient\Http\Client;

class DockerClientFactory
{
    public static function create(?string $dockerHost = null, ?bool $ssl = false, ?string $certPath = null): ApiClient
    {
        $options = [
            'remote_socket' => ($dockerHost ?: \getenv('DOCKER_HOST')) ?: 'unix:///var/run/docker.sock',
            'ssl' => ($ssl ?: \getenv('DOCKER_TLS_VERIFY')) ?: false,
        ];

        $certPath = ($certPath ?: \getenv('DOCKER_CERT_PATH')) ?: null;

        if ($certPath) {
            $options['stream_context_options'] = [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => $certPath,
                ],
            ];
        }

        $normalizers = [
            new ArrayDenormalizer(),
            new JaneObjectNormalizer(),
        ];

        $serializer = new Serializer(
            $normalizers,
            [
                new JsonEncoder(
                    new JsonEncode(),
                    new JsonDecode(['json_decode_associative' => true])
                ),
            ]
        );

        $requestFactory = $streamFactory = new Psr17Factory();

        return new ApiClient(
            new Client($options),
            $requestFactory,
            $serializer,
            $streamFactory
        );
    }
}
