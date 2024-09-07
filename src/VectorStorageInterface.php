<?php

declare(strict_types=1);

namespace LLM\Agents\VectorStorage;

use LLM\Agents\Embeddings\Document;
use LLM\Agents\Embeddings\Embedding;

interface VectorStorageInterface
{
    /**
     * Persist the provided documents.
     */
    public function persist(string $collection, Document ...$documents): void;

    /**
     * Search for similar documents based on the provided embedding.
     *
     * @return array<Document>
     */
    public function similaritySearch(string $collection, Embedding $embedding, int $limit): array;
}
