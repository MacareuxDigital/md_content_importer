<?php /** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter\Batches;

use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Macareux\ContentImporter\Search\ImportBatchLogList;

class Logs extends DashboardPageController
{
    public function view()
    {
        /** @var ImportBatchLogList $list */
        $list = $this->app->make(ImportBatchLogList::class);
        $factory = new PaginationFactory(Request::getInstance());
        $pagination = $factory->createPaginationObject($list, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->set('list', $list);
        $this->set('pagination', $pagination);
    }

    public function download_csv()
    {

    }
}