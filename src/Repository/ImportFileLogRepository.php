<?php

namespace Macareux\ContentImporter\Repository;

use Doctrine\ORM\EntityRepository;
use Macareux\ContentImporter\Entity\ImportFileLog;

class ImportFileLogRepository extends EntityRepository
{
    public function findOneByOriginal(string $original)
    {
        /** @var ImportFileLog $log */
        foreach ($this->findBy(['original' => $original]) as $log) {
            $f = $log->getImportedFile();
            if ($f) {
                return $log;
            }
        }

        return null;
    }
}
