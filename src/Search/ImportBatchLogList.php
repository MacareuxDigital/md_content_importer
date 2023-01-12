<?php

namespace Macareux\ContentImporter\Search;

use Concrete\Core\Search\ItemList\EntityItemList;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Macareux\ContentImporter\Entity\ImportBatchLog;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

class ImportBatchLogList extends EntityItemList implements PaginationProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getEntityManager()
    {
        return Application::getFacadeApplication()->make(EntityManagerInterface::class);
    }

    public function createQuery()
    {
        $this->query->select('l')->from(ImportBatchLog::class, 'l');
    }

    public function getResult($mixed)
    {
        return $mixed;
    }

    /**
     * @inheritDoc
     */
    public function getTotalResults()
    {
        $count = 0;
        $query = $this->query->select('count(distinct i.id)')
            ->setMaxResults(1)->resetDQLParts(['groupBy', 'orderBy']);

        try {
            $count = $query->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    function getPaginationAdapter()
    {
        return new QueryAdapter($this->deliverQueryObject());
    }
}
