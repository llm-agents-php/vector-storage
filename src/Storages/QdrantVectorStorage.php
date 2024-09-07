<?php

declare(strict_types=1);

namespace LLM\Agents\VectorStorage\Storages;

use LLM\Agents\Embeddings\Document;
use LLM\Agents\Embeddings\Embedding;
use LLM\Agents\Embeddings\EmbeddingGeneratorInterface;
use LLM\Agents\Embeddings\Source;
use LLM\Agents\VectorStorage\VectorStorageInterface;
use Qdrant\ClientInterface;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Request\CreateCollection;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\VectorStruct;

final readonly class QdrantVectorStorage implements VectorStorageInterface
{
    public function __construct(
        private ClientInterface $client,
        private EmbeddingGeneratorInterface $embeddingGenerator,
    ) {}

    public function hasCollection(string $collection): bool
    {
        return $this->client->collections($collection)->exists()['result']['exists'];
    }

    public function createCollection(string $collection, int $vectorLength = 1536): void
    {
        $createCollection = new CreateCollection();

        $createCollection->addVector(
            new VectorParams(size: $vectorLength, distance: VectorParams::DISTANCE_COSINE),
        );

        $this->client->collections($collection)->create($createCollection);
    }

    public function persist(string $collection, Document ...$documents): void
    {
        if ($documents === []) {
            return;
        }

        $withoutEmbeddings = \array_filter($documents, static fn(Document $document) => !$document->hasEmbedding());
        $withEmbeddings = \array_filter($documents, static fn(Document $document) => $document->hasEmbedding());

        if ($withoutEmbeddings !== []) {
            $withoutEmbeddings = $this->embeddingGenerator->generate(...$withoutEmbeddings);
        }

        $documents = [...$withoutEmbeddings, ...$withEmbeddings];

        $points = new PointsStruct();

        foreach ($documents as $document) {
            $points->addPoint($this->createPointFromDocument($document));
        }

        $this->client->collections($collection)->points()->upsert($points);
    }

    public function similaritySearch(string $collection, Embedding $embedding, int $limit): array
    {
        $vectorStruct = new VectorStruct($embedding->vector);
        $filter = new Filter();

        $searchRequest = (new SearchRequest($vectorStruct))
            ->setFilter($filter)
            ->setLimit($limit)
            ->setParams([
                'hnsw_ef' => 128,
                'exact' => true,
            ])
            ->setWithPayload(true);

        $response = $this->client->collections($collection)->points()->search($searchRequest);
        $results = $response['result'];

        if (\count($results) === 0) {
            return [];
        }

        $documents = [];
        foreach ($results as $point) {
            $documents[] = new Document(
                content: $point['payload']['document']['content'],
                source: new Source(
                    type: $point['payload']['document']['source']['type'],
                    metadata: $point['payload']['document']['source']['metadata'],
                ),
            );
        }

        return $documents;
    }

    private function createPointFromDocument(Document $document): PointStruct
    {
        return new PointStruct(
            id: $document->hash,
            vector: new VectorStruct($document->getEmbedding()->vector),
            payload: [
                'document' => $document,
            ],
        );
    }
}
