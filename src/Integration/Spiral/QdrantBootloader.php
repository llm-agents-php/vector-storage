<?php

declare(strict_types=1);

namespace LLM\Agents\VectorStorage\Integration\Spiral;

use LLM\Agents\VectorStorage\Storages\QdrantVectorStorage;
use LLM\Agents\VectorStorage\VectorStorageInterface;
use Qdrant\ClientInterface;
use Qdrant\Config;
use Qdrant\Http\Builder;
use Qdrant\Qdrant;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;

final class QdrantBootloader extends Bootloader
{
    public function defineSingletons(): array
    {
        return [
            ClientInterface::class => static fn(
                EnvironmentInterface $env,
            ): ClientInterface => new Qdrant(
                transport: (new Builder())->build(
                    (new Config(
                        host: $env->get('QDRANT_HOST'),
                    ))->setApiKey(
                        apiKey: $env->get('QDRANT_API_KEY'),
                    ),
                ),
            ),

            VectorStorageInterface::class => QdrantVectorStorage::class,
        ];
    }
}