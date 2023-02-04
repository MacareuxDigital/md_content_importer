<?php

/** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter\Batches;

use Concrete\Core\Csv\WriterFactory;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Macareux\ContentImporter\Export\FileLogCsvWriter;
use Macareux\ContentImporter\Search\ImportFileLogList;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileLogs extends DashboardPageController
{
    public function view()
    {
        /** @var ImportFileLogList $list */
        $list = $this->app->make(ImportFileLogList::class);
        $list->sortBy('l.import_date', 'DESC');
        $list->setItemsPerPage(20);
        $factory = new PaginationFactory(Request::getInstance());
        $pagination = $factory->createPaginationObject($list, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->set('list', $list);
        $this->set('pagination', $pagination);
        $this->set('headerMenu', $this->app->make(ElementManager::class)->get('content_importer/file_logs/menu', 'md_content_importer'));
    }

    public function export()
    {
        /** @var WriterFactory $factory */
        $factory = $this->app->make(WriterFactory::class);
        $file = new \SplFileObject('php://output', 'w');
        $csv = $factory->createFromFileObject($file);

        $config = $this->app->make('config');
        $bom = $config->get('concrete.export.csv.include_bom') ? $config->get('concrete.charset_bom') : '';

        /** @var FileLogCsvWriter $writer */
        $writer = $this->app->make(FileLogCsvWriter::class, [
            'writer' => $csv,
        ]);

        /** @var ImportFileLogList $list */
        $list = $this->app->make(ImportFileLogList::class);

        return new StreamedResponse(
            function () use ($bom, $writer, $list) {
                if ($bom) {
                    echo $bom;
                }
                $writer->insertHeaders();
                $writer->insertEntryList($list);
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="import_logs_' . date('Ymdhis') . '.csv"',
            ]
        );
    }
}
