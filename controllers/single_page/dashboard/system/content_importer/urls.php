<?php

/** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter;

use Carbon\CarbonImmutable;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Concrete\Core\Utility\Service\Validation\Numbers;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\ContentImporter\Command\ImportUrlCsvChunkCommand;
use Macareux\ContentImporter\Csv\UrlCsvImporter;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\ImportUrl;
use Macareux\ContentImporter\Search\ImportUrlList;
use Macareux\ContentImporter\Service\ImportUrlStatusSync;
use Macareux\ContentImporter\Service\UrlBatchAssigner;
use Macareux\ContentImporter\Traits\EntityTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Urls extends DashboardPageController
{
    use EntityTrait;

    private const SESSION_CSV_KEY = 'md_content_importer.csv_import';

    protected function getCsvSession()
    {
        return $this->app->make('session');
    }

    public function view()
    {
        /** @var ImportUrlList $list */
        $list = $this->app->make(ImportUrlList::class);
        $list->setItemsPerPage(50);

        $sort = (string) $this->request->query->get('sort', 'url');
        $sortColumns = [
            'id' => 'u.id',
            'url' => 'u.url',
            'import_date' => 'u.importDate',
        ];
        if (!isset($sortColumns[$sort])) {
            $sort = 'url';
        }

        $direction = strtolower((string) $this->request->query->get('direction', 'asc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }
        $list->sortBy($sortColumns[$sort], strtoupper($direction));

        $keywords = trim((string) $this->request->query->get('keywords', ''));
        if ($keywords !== '') {
            $list->filterByKeywords($keywords);
        }

        $status = (string) $this->request->query->get('status', '');
        if ($status !== '' && array_key_exists($status, ImportUrl::getStatusOptions())) {
            $list->filterByStatus($status);
        }

        $batchFilter = (string) $this->request->query->get('batch_id', '');
        if ($batchFilter === 'unassigned') {
            $list->filterByUnassigned();
        } elseif ($batchFilter === 'manual') {
            $list->filterByManual();
        } elseif ($batchFilter !== '' && ctype_digit($batchFilter) && (int) $batchFilter > 0) {
            $list->filterByBatchId((int) $batchFilter);
        }

        $factory = new PaginationFactory(Request::getInstance());
        $pagination = $factory->createPaginationObject($list, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);

        $batchOptions = [
            '' => t('** All Batches'),
            'unassigned' => t('Unassigned'),
            'manual' => t('Manual'),
        ];
        $assignBatchOptions = ['' => t('** Select Batch')];
        foreach ($this->getAll(Batch::class) as $batch) {
            /** @var Batch $batch */
            $batchOptions[$batch->getId()] = $batch->getName();
            $assignBatchOptions[$batch->getId()] = $batch->getName();
        }

        $this->set('list', $list);
        $this->set('pagination', $pagination);
        $this->set('keywords', $keywords);
        $this->set('status', $status);
        $this->set('batchId', $batchFilter);
        $this->set('sort', $sort);
        $this->set('direction', $direction);
        $this->set('statusOptions', ['' => t('** All Statuses')] + ImportUrl::getStatusOptions());
        $this->set('batchOptions', $batchOptions);
        $this->set('assignBatchOptions', $assignBatchOptions);
        $this->set('token', $this->token);
        $this->set('headerMenu', $this->app->make(ElementManager::class)->get('dashboard/urls/header', 'md_content_importer'));
    }

    public function import_csv()
    {
        $this->set('pageTitle', t('Import URL CSV'));
        $this->set('token', $this->token);
        $this->render('/dashboard/system/content_importer/urls/import_csv');
    }

    public function upload_csv()
    {
        if (!$this->token->validate('upload_csv')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var UploadedFile|null $file */
        $file = $this->request->files->get('csv_file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            $this->error->add(t('Please upload a CSV file.'));
        } else {
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'csv') {
                $this->error->add(t('The uploaded file must be a CSV file.'));
            }
        }

        if (!$this->error->has()) {
            /** @var \Concrete\Core\File\Service\File $fileService */
            $fileService = $this->app->make('helper/file');
            $tmpDir = $fileService->getTemporaryDirectory();
            $safeName = 'md_ci_urls_' . bin2hex(random_bytes(16)) . '.csv';
            $targetPath = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

            $realTmp = realpath($tmpDir);
            if ($realTmp === false) {
                $this->error->add(t('Unable to resolve temporary directory.'));
            } else {
                $file->move($tmpDir, $safeName);
                $realPath = realpath($targetPath);
                if ($realPath === false || strpos($realPath, $realTmp) !== 0) {
                    $this->error->add(t('Invalid upload path.'));
                } else {
                    try {
                        /** @var UrlCsvImporter $importer */
                        $importer = $this->app->make(UrlCsvImporter::class);
                        $headers = $importer->getHeaders($realPath);
                        if ($headers === []) {
                            $this->error->add(t('CSV file has no header row.'));
                            @unlink($realPath);
                        } else {
                            $this->getCsvSession()->set(self::SESSION_CSV_KEY, [
                                'path' => $realPath,
                                'headers' => $headers,
                            ]);

                            return $this->buildRedirect($this->action('map_csv'));
                        }
                    } catch (\Throwable $e) {
                        $this->error->add(t('Unable to read CSV file: %s', $e->getMessage()));
                        @unlink($targetPath);
                    }
                }
            }
        }

        $this->import_csv();
    }

    public function map_csv()
    {
        $state = $this->getCsvSession()->get(self::SESSION_CSV_KEY);
        if (!is_array($state) || empty($state['path']) || empty($state['headers'])) {
            $this->error->add(t('Please upload a CSV file first.'));
            $this->import_csv();

            return;
        }

        $path = (string) $state['path'];
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            $this->getCsvSession()->remove(self::SESSION_CSV_KEY);
            $this->error->add(t('Uploaded CSV file is no longer available. Please upload again.'));
            $this->import_csv();

            return;
        }

        $headers = $state['headers'];
        $headerOptions = [];
        foreach ($headers as $header) {
            $headerOptions[$header] = $header;
        }

        $this->set('pageTitle', t('Map CSV Columns'));
        $this->set('token', $this->token);
        $this->set('headers', $headers);
        $this->set('headerOptions', $headerOptions);
        $this->set('urlColumn', UrlCsvImporter::suggestUrlColumn($headers) ?? '');
        $this->set('titleColumn', UrlCsvImporter::suggestTitleColumn($headers) ?? '');
        $this->set('defaultFilters', self::defaultFiltersForHeaders($headers));
        $this->set('operatorOptions', [
            'equals' => t('Equals'),
            'contains' => t('Contains'),
        ]);
        $this->render('/dashboard/system/content_importer/urls/map_csv');
    }

    public function submit_csv_import()
    {
        if (!$this->token->validate('submit_csv_import')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $state = $this->getCsvSession()->get(self::SESSION_CSV_KEY);
        if (!is_array($state) || empty($state['path']) || empty($state['headers'])) {
            $this->error->add(t('Please upload a CSV file first.'));
        }

        $urlColumn = (string) $this->post('urlColumn');
        $titleColumn = (string) $this->post('titleColumn');
        $headers = is_array($state['headers'] ?? null) ? $state['headers'] : [];

        if ($urlColumn === '' || !in_array($urlColumn, $headers, true)) {
            $this->error->add(t('Please select a valid URL column.'));
        }
        if ($titleColumn !== '' && !in_array($titleColumn, $headers, true)) {
            $this->error->add(t('Please select a valid title column.'));
        }

        $filters = $this->parseFilters($headers);
        $path = realpath((string) ($state['path'] ?? ''));
        if ($path === false || !is_file($path)) {
            $this->error->add(t('Uploaded CSV file is no longer available. Please upload again.'));
        }

        if (!$this->error->has()) {
            try {
                /** @var UrlCsvImporter $importer */
                $importer = $this->app->make(UrlCsvImporter::class);
                $rows = $importer->getMatchingRows(
                    $path,
                    $urlColumn,
                    $titleColumn !== '' ? $titleColumn : null,
                    $filters
                );

                if ($rows === []) {
                    $this->error->add(t('No rows matched the selected filters.'));
                } else {
                    $chunks = array_chunk($rows, UrlCsvImporter::CHUNK_SIZE);
                    $commandBatch = \Concrete\Core\Command\Batch\Batch::create(t('Import URLs'), function () use ($chunks) {
                        foreach ($chunks as $chunk) {
                            $command = new ImportUrlCsvChunkCommand();
                            $command->setRows($chunk);
                            yield $command;
                        }
                    });

                    $this->getCsvSession()->remove(self::SESSION_CSV_KEY);
                    @unlink($path);

                    return $this->dispatchBatch($commandBatch);
                }
            } catch (\Throwable $e) {
                $this->error->add(t('Unable to import CSV: %s', $e->getMessage()));
            }
        }

        $this->map_csv();
    }

    public function import_completed()
    {
        $this->flash('success', t('CSV import completed.'));

        return $this->buildRedirect($this->action('view'));
    }

    public function assign_to_batch()
    {
        if (!$this->token->validate('assign_to_batch')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $batchId = (int) $this->post('assign_batch_id');
        /** @var Batch|null $batch */
        $batch = $this->getEntry(Batch::class, $batchId);
        if (!$batch) {
            $this->error->add(t('Please select a valid batch.'));
        }

        $urls = $this->getSelectedImportUrls();
        if ($urls === []) {
            $this->error->add(t('Please select at least one URL.'));
        }

        if (!$this->error->has()) {
            /** @var UrlBatchAssigner $assigner */
            $assigner = $this->app->make(UrlBatchAssigner::class);
            $errors = [];
            $assigned = $assigner->assignUrlsToBatch($urls, $batch, $errors);
            foreach ($errors as $message) {
                $this->error->add($message);
            }

            if ($assigned > 0) {
                $this->flash('success', t2('%s URL assigned to batch.', '%s URLs assigned to batch.', $assigned));
            }

            if (!$this->error->has()) {
                return $this->buildRedirect($this->action('view'));
            }
        }

        $this->view();
    }

    public function unassign()
    {
        if (!$this->token->validate('unassign')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $urls = $this->getSelectedImportUrls();
        if ($urls === []) {
            $this->error->add(t('Please select at least one URL.'));
        }

        if (!$this->error->has()) {
            /** @var UrlBatchAssigner $assigner */
            $assigner = $this->app->make(UrlBatchAssigner::class);
            $unassigned = $assigner->unassignUrls($urls);
            $this->flash('success', t2('%s URL unassigned.', '%s URLs unassigned.', $unassigned));

            return $this->buildRedirect($this->action('view'));
        }

        $this->view();
    }

    public function remove_urls()
    {
        if (!$this->token->validate('remove_urls')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $urls = $this->getSelectedImportUrls();
        if ($urls === []) {
            $this->error->add(t('Please select at least one URL.'));
        }

        if (!$this->error->has()) {
            /** @var UrlBatchAssigner $assigner */
            $assigner = $this->app->make(UrlBatchAssigner::class);
            $assigner->unassignUrls($urls);

            foreach ($urls as $importUrl) {
                $this->entityManager->remove($importUrl);
            }
            $this->entityManager->flush();

            $removed = count($urls);
            $this->flash('success', t2('%s URL removed.', '%s URLs removed.', $removed));

            return $this->buildRedirect($this->action('view'));
        }

        $this->view();
    }

    public function set_imported_page($id)
    {
        /** @var ImportUrl|null $importUrl */
        $importUrl = $this->getEntry(ImportUrl::class, (int) $id);
        if (!$importUrl) {
            $this->error->add(t('Invalid URL.'));
            $this->view();

            return;
        }

        $this->set('importUrl', $importUrl);
        $this->set('pageSelector', $this->app->make('helper/form/page_selector'));
        $this->set('token', $this->token);
        $this->set('pageTitle', t('Set Imported Page'));
        $this->render('/dashboard/system/content_importer/urls/set_imported_page');
    }

    public function submit_imported_page()
    {
        if (!$this->token->validate('submit_imported_page')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var ImportUrl|null $importUrl */
        $importUrl = $this->getEntry(ImportUrl::class, (int) $this->post('url_id'));
        if (!$importUrl) {
            $this->error->add(t('Invalid URL.'));
        }

        $page = Page::getByID((int) $this->post('imported_cID'));
        if (!$page || $page->isError()) {
            $this->error->add(t('Please select a valid imported page.'));
        } else {
            $permissions = new Checker($page);
            if (!$permissions->canViewPage()) {
                $this->error->add(t('You do not have permission to view the selected page.'));
            }
        }

        if (!$this->error->has()) {
            if ($importUrl->getBatch()) {
                /** @var UrlBatchAssigner $assigner */
                $assigner = $this->app->make(UrlBatchAssigner::class);
                $assigner->unassignUrls([$importUrl]);
            }

            $importUrl->setImportedPage($page);
            $importUrl->setImportDate(CarbonImmutable::now());
            $importUrl->setManuallyImported(true);
            $this->entityManager->persist($importUrl);
            $this->entityManager->flush();

            $this->flash('success', t('Imported page set successfully.'));

            return $this->buildRedirect($this->action('view'));
        }

        if ($importUrl) {
            $this->set_imported_page($importUrl->getId());
        } else {
            $this->view();
        }
    }

    public function refresh_status()
    {
        if (!$this->token->validate('refresh_status')) {
            $this->error->add($this->token->getErrorMessage());
            $this->view();

            return;
        }

        /** @var ImportUrlStatusSync $sync */
        $sync = $this->app->make(ImportUrlStatusSync::class);
        $updated = $sync->refreshFromLogs();
        $this->flash('success', t2('%s URL updated from import logs.', '%s URLs updated from import logs.', $updated));

        return $this->buildRedirect($this->action('view'));
    }

    /**
     * @return ImportUrl[]
     */
    protected function getSelectedImportUrls(): array
    {
        $ids = $this->post('url_ids');
        if (!is_array($ids)) {
            return [];
        }

        /** @var Numbers $valn */
        $valn = $this->app->make('helper/validation/numbers');
        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        $urls = [];

        foreach ($ids as $id) {
            if (!$valn->integer($id, 1)) {
                continue;
            }
            $importUrl = $em->find(ImportUrl::class, (int) $id);
            if ($importUrl) {
                $urls[] = $importUrl;
            }
        }

        return $urls;
    }

    /**
     * @param string[] $headers
     *
     * @return array{column: string, operator: string, value: string}[]
     */
    protected static function defaultFiltersForHeaders(array $headers): array
    {
        $filters = [];
        if (in_array('Content Type', $headers, true)) {
            $filters[] = [
                'column' => 'Content Type',
                'operator' => 'equals',
                'value' => 'text/html; charset=UTF-8',
            ];
        }
        if (in_array('Status Code', $headers, true)) {
            $filters[] = [
                'column' => 'Status Code',
                'operator' => 'equals',
                'value' => '200',
            ];
        }
        if ($filters === []) {
            $filters[] = [
                'column' => '',
                'operator' => 'equals',
                'value' => '',
            ];
        }

        return $filters;
    }

    /**
     * @param string[] $headers
     *
     * @return array{column: string, operator: string, value: string}[]
     */
    protected function parseFilters(array $headers): array
    {
        $columns = $this->post('filter_column');
        $operators = $this->post('filter_operator');
        $values = $this->post('filter_value');
        if (!is_array($columns)) {
            return [];
        }

        $filters = [];
        foreach ($columns as $index => $column) {
            $column = (string) $column;
            if ($column === '' || !in_array($column, $headers, true)) {
                continue;
            }
            $operator = (string) ($operators[$index] ?? 'equals');
            if (!in_array($operator, ['equals', 'contains'], true)) {
                $operator = 'equals';
            }
            $value = (string) ($values[$index] ?? '');
            if ($value === '') {
                continue;
            }
            $filters[] = [
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $filters;
    }
}
