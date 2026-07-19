<?php

namespace Macareux\ContentImporter\Repository;

use Doctrine\ORM\EntityRepository;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\ImportUrl;

class ImportUrlRepository extends EntityRepository
{
    public function findOneByUrl(string $url): ?ImportUrl
    {
        return $this->findOneBy(['url' => $url]);
    }

    /**
     * @return ImportUrl[]
     */
    public function findByBatch(Batch $batch): array
    {
        return $this->findBy(['batch' => $batch]);
    }

    /**
     * @param string[] $urls
     *
     * @return ImportUrl[]
     */
    public function findByUrls(array $urls): array
    {
        if ($urls === []) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where('u.url IN (:urls)')
            ->setParameter('urls', $urls)
            ->getQuery()
            ->getResult();
    }
}
