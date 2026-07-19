<?php

namespace Macareux\ContentImporter\Service;

use Doctrine\ORM\EntityManagerInterface;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\ImportUrl;
use Macareux\ContentImporter\Repository\ImportUrlRepository;

class UrlBatchAssigner
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ImportUrl[] $urls
     * @param string[] $errors
     *
     * @return int number of URLs assigned
     */
    public function assignUrlsToBatch(array $urls, Batch $batch, array &$errors = []): int
    {
        $documentRoot = $batch->getDocumentRoot();
        $assigned = 0;

        foreach ($urls as $importUrl) {
            if (!$importUrl instanceof ImportUrl) {
                continue;
            }

            $url = $importUrl->getUrl();
            if ($documentRoot !== '' && strpos($url, $documentRoot) !== 0) {
                $errors[] = t('URL does not match batch document root: %s', $url);
                continue;
            }

            $previousBatch = $importUrl->getBatch();
            if ($previousBatch && $previousBatch->getId() !== $batch->getId()) {
                $this->removeUrlFromSourcePath($previousBatch, $url);
            }

            $this->appendUrlToSourcePath($batch, $url);
            $importUrl->setBatch($batch);
            $this->entityManager->persist($importUrl);
            ++$assigned;
        }

        $this->entityManager->persist($batch);
        $this->entityManager->flush();

        return $assigned;
    }

    /**
     * @param ImportUrl[] $urls
     *
     * @return int number of URLs unassigned
     */
    public function unassignUrls(array $urls): int
    {
        $unassigned = 0;

        foreach ($urls as $importUrl) {
            if (!$importUrl instanceof ImportUrl) {
                continue;
            }

            $batch = $importUrl->getBatch();
            if (!$batch) {
                continue;
            }

            $this->removeUrlFromSourcePath($batch, $importUrl->getUrl());
            $importUrl->setBatch(null);
            $this->entityManager->persist($importUrl);
            $this->entityManager->persist($batch);
            ++$unassigned;
        }

        $this->entityManager->flush();

        return $unassigned;
    }

    /**
     * Sync inventory assignments from a batch's sourcePath text.
     */
    public function syncFromBatchSourcePath(Batch $batch): void
    {
        /** @var ImportUrlRepository $repository */
        $repository = $this->entityManager->getRepository(ImportUrl::class);

        $paths = [];
        foreach (preg_split('/\R/', $batch->getSourcePath()) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $paths[] = $line;
            }
        }
        $paths = array_unique($paths);

        $currentlyAssigned = $repository->findByBatch($batch);
        foreach ($currentlyAssigned as $importUrl) {
            if (!in_array($importUrl->getUrl(), $paths, true)) {
                $importUrl->setBatch(null);
                $this->entityManager->persist($importUrl);
            }
        }

        if ($paths !== []) {
            foreach ($repository->findByUrls($paths) as $importUrl) {
                $previousBatch = $importUrl->getBatch();
                if ($previousBatch && $previousBatch->getId() !== $batch->getId()) {
                    $this->removeUrlFromSourcePath($previousBatch, $importUrl->getUrl());
                    $this->entityManager->persist($previousBatch);
                }
                $importUrl->setBatch($batch);
                $this->entityManager->persist($importUrl);
            }
        }

        $this->entityManager->flush();
    }

    protected function appendUrlToSourcePath(Batch $batch, string $url): void
    {
        $lines = [];
        foreach (preg_split('/\R/', $batch->getSourcePath()) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        if (!in_array($url, $lines, true)) {
            $lines[] = $url;
        }

        $batch->setSourcePath(implode(PHP_EOL, $lines));
    }

    protected function removeUrlFromSourcePath(Batch $batch, string $url): void
    {
        $lines = [];
        foreach (preg_split('/\R/', $batch->getSourcePath()) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && $line !== $url) {
                $lines[] = $line;
            }
        }

        $batch->setSourcePath(implode(PHP_EOL, $lines));
    }
}
