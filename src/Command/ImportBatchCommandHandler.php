<?php

namespace Macareux\ContentImporter\Command;

use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Publisher\BatchPublisher;

class ImportBatchCommandHandler
{
    public function __invoke(ImportBatchCommand $command)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        $batch = $entityManager->find(Batch::class, $command->getBatchID());
        $sourcePath = $command->getSourcePath();
        if ($batch && $sourcePath) {
            /** @var BatchPublisher $publisher */
            $publisher = $app->make(BatchPublisher::class, ['batch' => $batch]);
            $publisher->publish($sourcePath);
        }
    }
}
