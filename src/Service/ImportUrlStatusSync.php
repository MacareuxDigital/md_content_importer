<?php

namespace Macareux\ContentImporter\Service;

use Doctrine\ORM\EntityManagerInterface;
use Macareux\ContentImporter\Entity\ImportBatchLog;
use Macareux\ContentImporter\Entity\ImportUrl;
use Macareux\ContentImporter\Repository\ImportBatchLogRepository;
use Macareux\ContentImporter\Repository\ImportUrlRepository;

class ImportUrlStatusSync
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function markImportedFromLog(ImportBatchLog $log): void
    {
        /** @var ImportUrlRepository $repository */
        $repository = $this->entityManager->getRepository(ImportUrl::class);
        $importUrl = $repository->findOneByUrl($log->getOriginal());
        if (!$importUrl) {
            return;
        }

        $page = $log->getImportedPage();
        if ($page) {
            $importUrl->setImportedPage($page);
        } else {
            $importUrl->setImportedCID($log->getImportedCID());
        }
        $importUrl->setImportDate($log->getImportDate());
        $importUrl->setManuallyImported(false);
        if ($log->getBatch()) {
            $importUrl->setBatch($log->getBatch());
        }
        $this->entityManager->persist($importUrl);
        $this->entityManager->flush();
    }

    /**
     * @return int number of URLs updated
     */
    public function refreshFromLogs(): int
    {
        /** @var ImportUrlRepository $urlRepository */
        $urlRepository = $this->entityManager->getRepository(ImportUrl::class);
        /** @var ImportBatchLogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(ImportBatchLog::class);

        $updated = 0;
        /** @var ImportUrl $importUrl */
        foreach ($urlRepository->findAll() as $importUrl) {
            if ($importUrl->isManuallyImported()) {
                continue;
            }

            $log = $logRepository->findOneByOriginal($importUrl->getUrl());
            if (!$log) {
                continue;
            }

            $page = $log->getImportedPage();
            if ($page) {
                $importUrl->setImportedPage($page);
            } else {
                $importUrl->setImportedCID($log->getImportedCID());
            }
            $importUrl->setImportDate($log->getImportDate());
            if ($log->getBatch() && !$importUrl->getBatch()) {
                $importUrl->setBatch($log->getBatch());
            }
            $this->entityManager->persist($importUrl);
            ++$updated;

            if ($updated % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        return $updated;
    }
}
