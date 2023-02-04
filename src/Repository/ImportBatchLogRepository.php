<?php

namespace Macareux\ContentImporter\Repository;

use Concrete\Core\Page\Page;
use Doctrine\ORM\EntityRepository;
use Macareux\ContentImporter\Entity\ImportBatchLog;

class ImportBatchLogRepository extends EntityRepository
{
    public function findOneByOriginal(string $original): ?ImportBatchLog
    {
        /** @var ImportBatchLog $log */
        foreach ($this->findBy(['original' => $original]) as $log) {
            $c = $log->getImportedPage();
            if ($c) {
                return $log;
            }
        }

        return null;
    }
}