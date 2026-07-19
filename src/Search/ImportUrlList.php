<?php

namespace Macareux\ContentImporter\Search;

use Concrete\Core\Search\ItemList\EntityItemList;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Macareux\ContentImporter\Entity\ImportUrl;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

class ImportUrlList extends EntityItemList implements PaginationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityManager()
    {
        return Application::getFacadeApplication()->make(EntityManagerInterface::class);
    }

    public function createQuery()
    {
        $this->query->select('u')->from(ImportUrl::class, 'u')->leftJoin('u.batch', 'b');
    }

    public function getResult($mixed)
    {
        return $mixed;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalResults()
    {
        $count = 0;
        $query = $this->query->select('count(distinct u.id)')
            ->setMaxResults(1)->resetDQLParts(['groupBy', 'orderBy']);

        try {
            $count = $query->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginationAdapter()
    {
        return new QueryAdapter($this->deliverQueryObject());
    }

    public function filterByKeywords(string $keywords): void
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return;
        }

        $this->query->andWhere($this->query->expr()->like('u.url', ':keywords'))
            ->setParameter('keywords', '%' . $keywords . '%');
    }

    public function filterByBatchId(int $batchId): void
    {
        $this->query->andWhere('b.id = :batchId')
            ->setParameter('batchId', $batchId);
    }

    public function filterByStatus(string $status): void
    {
        switch ($status) {
            case ImportUrl::STATUS_IMPORTED:
                $this->query->andWhere('u.importedCID IS NOT NULL');
                break;
            case ImportUrl::STATUS_ASSIGNED:
                $this->query->andWhere('u.batch IS NOT NULL')
                    ->andWhere('u.importedCID IS NULL');
                break;
            case ImportUrl::STATUS_NOT_YET:
                $this->query->andWhere('u.batch IS NULL')
                    ->andWhere('u.importedCID IS NULL');
                break;
        }
    }
}
