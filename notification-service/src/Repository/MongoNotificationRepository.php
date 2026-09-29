<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoConfig;
use App\Dto\CreateNotificationDto;
use App\Dto\NotificationDto;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use RuntimeException;

final class MongoNotificationRepository implements NotificationRepositoryInterface
{
    private readonly Collection $collection;

    public function __construct(MongoConfig $config)
    {
        $client = new Client($config->uri);
        $this->collection = $client->selectCollection($config->database, 'notifications');
    }

    public function create(CreateNotificationDto $dto): NotificationDto
    {
        $now = new UTCDateTime();

        $document = [
            'event' => $dto->event,
            'channel' => $dto->channel->value,
            'recipient' => $dto->recipient,
            'payload' => $dto->payload,
            'status' => NotificationStatus::Pending->value,
            'attempts' => 0,
            'createdAt' => $now,
            'updatedAt' => $now,
            'sentAt' => null,
            'lastError' => null,
            'deadLettered' => false,
        ];

        $result = $this->collection->insertOne($document);
        $document['_id'] = $result->getInsertedId();

        return $this->hydrate($document);
    }

    public function markSent(string $id): NotificationDto
    {
        return $this->applyUpdate($id, [
            'status' => NotificationStatus::Sent->value,
            'sentAt' => new UTCDateTime(),
            'lastError' => null,
        ], incrementAttempts: true);
    }

    public function markRetrying(string $id, string $error): NotificationDto
    {
        return $this->applyUpdate($id, [
            'status' => NotificationStatus::Retrying->value,
            'lastError' => $error,
        ], incrementAttempts: true);
    }

    public function markFailed(string $id, string $error, bool $deadLettered): NotificationDto
    {
        return $this->applyUpdate($id, [
            'status' => NotificationStatus::Failed->value,
            'lastError' => $error,
            'deadLettered' => $deadLettered,
        ], incrementAttempts: true);
    }

    public function resetForReplay(string $id): NotificationDto
    {
        return $this->applyUpdate($id, [
            'status' => NotificationStatus::Pending->value,
            'attempts' => 0,
            'lastError' => null,
            'deadLettered' => false,
            'sentAt' => null,
        ], incrementAttempts: false);
    }

    public function findById(string $id): ?NotificationDto
    {
        $document = $this->collection->findOne(['_id' => new ObjectId($id)]);

        return $document !== null ? $this->hydrate($document) : null;
    }

    public function findAll(int $limit, int $offset, array $filters): array
    {
        $cursor = $this->collection->find(
            $this->buildFilter($filters),
            ['limit' => $limit, 'skip' => $offset, 'sort' => ['createdAt' => -1]],
        );

        return array_map($this->hydrate(...), iterator_to_array($cursor));
    }

    public function countAll(array $filters): int
    {
        return $this->collection->countDocuments($this->buildFilter($filters));
    }

    private function applyUpdate(string $id, array $set, bool $incrementAttempts): NotificationDto
    {
        $objectId = new ObjectId($id);
        $set['updatedAt'] = new UTCDateTime();

        $update = ['$set' => $set];

        if ($incrementAttempts) {
            $update['$inc'] = ['attempts' => 1];
        }

        $this->collection->updateOne(['_id' => $objectId], $update);

        return $this->findById($id) ?? throw new RuntimeException("Notification [{$id}] could not be updated.");
    }

    private function buildFilter(array $filters): array
    {
        $filter = [];

        foreach (['status', 'channel', 'recipient'] as $key) {
            if (isset($filters[$key])) {
                $filter[$key] = $filters[$key];
            }
        }

        return $filter;
    }

    private function hydrate(array|BSONDocument $document): NotificationDto
    {
        $sentAt = $document['sentAt'] ?? null;

        return new NotificationDto(
            (string) $document['_id'],
            (string) $document['event'],
            NotificationChannel::from((string) $document['channel']),
            (string) $document['recipient'],
            (array) $document['payload'],
            NotificationStatus::from((string) $document['status']),
            (int) $document['attempts'],
            $this->formatDate($document['createdAt']),
            $this->formatDate($document['updatedAt']),
            $sentAt instanceof UTCDateTime ? $this->formatDate($sentAt) : null,
            isset($document['lastError']) ? (string) $document['lastError'] : null,
            (bool) ($document['deadLettered'] ?? false),
        );
    }

    private function formatDate(UTCDateTime $date): string
    {
        return $date->toDateTime()->format(DATE_ATOM);
    }
}
