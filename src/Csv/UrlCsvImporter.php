<?php

namespace Macareux\ContentImporter\Csv;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Macareux\ContentImporter\Entity\ImportUrl;
use Macareux\ContentImporter\Repository\ImportUrlRepository;

class UrlCsvImporter
{
    public const CHUNK_SIZE = 50;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return string[]
     */
    public function getHeaders(string $filePath): array
    {
        $reader = $this->createReader($filePath);

        return $reader->getHeader();
    }

    /**
     * @param array{column: string, operator: string, value: string}[] $filters
     *
     * @return array{url: string, title: ?string, metadata: array}[]
     */
    public function getMatchingRows(string $filePath, string $urlColumn, ?string $titleColumn, array $filters = []): array
    {
        $reader = $this->createReader($filePath);
        $headers = $reader->getHeader();
        if (!in_array($urlColumn, $headers, true)) {
            throw new \InvalidArgumentException(t('Invalid URL column.'));
        }
        if ($titleColumn !== null && $titleColumn !== '' && !in_array($titleColumn, $headers, true)) {
            throw new \InvalidArgumentException(t('Invalid title column.'));
        }

        $rows = [];
        foreach ($reader->getRecords() as $record) {
            if (!$this->matchesFilters($record, $filters)) {
                continue;
            }

            $url = trim((string) ($record[$urlColumn] ?? ''));
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $title = null;
            if ($titleColumn !== null && $titleColumn !== '') {
                $titleValue = trim((string) ($record[$titleColumn] ?? ''));
                if ($titleValue !== '') {
                    $title = mb_substr($titleValue, 0, 255);
                }
            }

            $metadata = [];
            foreach (['Content Type', 'Status Code', 'Status', 'Indexability'] as $metaColumn) {
                if (array_key_exists($metaColumn, $record) && $record[$metaColumn] !== null && $record[$metaColumn] !== '') {
                    $metadata[$metaColumn] = (string) $record[$metaColumn];
                }
            }

            $rows[] = [
                'url' => $url,
                'title' => $title,
                'metadata' => $metadata,
            ];
        }

        return $rows;
    }

    /**
     * @param array{url: string, title: ?string, metadata: array}[] $rows
     *
     * @return array{created: int, updated: int}
     */
    public function upsertRows(array $rows): array
    {
        /** @var ImportUrlRepository $repository */
        $repository = $this->entityManager->getRepository(ImportUrl::class);
        $created = 0;
        $updated = 0;
        $now = CarbonImmutable::now();

        foreach ($rows as $index => $row) {
            $url = $row['url'] ?? '';
            if ($url === '') {
                continue;
            }

            $importUrl = $repository->findOneByUrl($url);
            if ($importUrl) {
                if (!empty($row['title'])) {
                    $importUrl->setTitle($row['title']);
                }
                if (!empty($row['metadata'])) {
                    $existing = $importUrl->getMetadata() ?: [];
                    $importUrl->setMetadata(array_merge($existing, $row['metadata']));
                }
                ++$updated;
            } else {
                $importUrl = new ImportUrl();
                $importUrl->setUrl($url);
                $importUrl->setTitle($row['title'] ?? null);
                $importUrl->setMetadata($row['metadata'] ?? null);
                $importUrl->setDateAdded($now);
                ++$created;
            }

            $this->entityManager->persist($importUrl);

            if (($index + 1) % self::CHUNK_SIZE === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    public static function suggestUrlColumn(array $headers): ?string
    {
        foreach (['Address', 'URL', 'url', 'Uri', 'URI'] as $name) {
            if (in_array($name, $headers, true)) {
                return $name;
            }
        }

        return $headers[0] ?? null;
    }

    public static function suggestTitleColumn(array $headers): ?string
    {
        foreach (['Title 1', 'Title', 'title', 'H1-1'] as $name) {
            if (in_array($name, $headers, true)) {
                return $name;
            }
        }

        return null;
    }

    protected function createReader(string $filePath): Reader
    {
        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);
        $reader->skipInputBOM();

        return $reader;
    }

    /**
     * @param array<string, string|null> $record
     * @param array{column: string, operator: string, value: string}[] $filters
     */
    protected function matchesFilters(array $record, array $filters): bool
    {
        foreach ($filters as $filter) {
            $column = $filter['column'] ?? '';
            $operator = $filter['operator'] ?? 'equals';
            $value = (string) ($filter['value'] ?? '');
            if ($column === '') {
                continue;
            }

            $cell = (string) ($record[$column] ?? '');
            if ($operator === 'contains') {
                if (mb_stripos($cell, $value) === false) {
                    return false;
                }
            } elseif ($cell !== $value) {
                return false;
            }
        }

        return true;
    }
}
