<?php

declare(strict_types=1);

namespace LLM\Agents\VectorStorage;

use LLM\Agents\Embeddings\Embedding;
use LLM\Agents\Embeddings\EmbeddingRepositoryInterface;

final readonly class VectorStorageEmbeddingRepository implements EmbeddingRepositoryInterface
{
    public function __construct(
        private VectorStorageInterface $storage,
        private string $collection,
    ) {}

    public function search(Embedding $embedding, int $limit = 5): array
    {
        return $this->storage->similaritySearch($this->collection, $embedding, $limit);
    }
}
