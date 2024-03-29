<?php

/** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter;

use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Composer\FormLayoutSet;
use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Search\Pagination\PaginationFactory;
use Macareux\ContentImporter\Command\ImportBatchCommand;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Entity\BatchItemTransformer;
use Macareux\ContentImporter\Entity\ImportBatchLog;
use Macareux\ContentImporter\Http\Crawler;
use Macareux\ContentImporter\Http\PreviewResponse;
use Macareux\ContentImporter\Repository\ImportBatchLogRepository;
use Macareux\ContentImporter\Search\BatchList;
use Macareux\ContentImporter\Traits\EntityTrait;
use Macareux\ContentImporter\Traits\PermissionCheckerTrait;
use Macareux\ContentImporter\Transformer\TransformerManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class Batches extends DashboardPageController
{
    use PermissionCheckerTrait;

    use EntityTrait;

    public function view()
    {
        /** @var BatchList $list */
        $list = $this->app->make(BatchList::class);
        $list->sortBy('b.id', 'DESC');
        $list->setItemsPerPage(20);
        $factory = new PaginationFactory(Request::getInstance());
        $pagination = $factory->createPaginationObject($list, PaginationFactory::PERMISSIONED_PAGINATION_STYLE_PAGER);
        $this->set('list', $list);
        $this->set('pagination', $pagination);
        $this->set('token', $this->app->make('token'));
        $this->set('headerMenu', $this->app->make(ElementManager::class)->get('dashboard/batches/header', 'md_content_importer'));
    }

    public function add_batch()
    {
        $pageTypeIDs = ['' => t('** Select Page Type')];
        $pageTypes = Type::getList();
        /** @var Type $pageType */
        foreach ($pageTypes as $pageType) {
            $pageTypeIDs[$pageType->getPageTypeID()] = $pageType->getPageTypeDisplayName();
        }
        $this->set('pageTypeIDs', $pageTypeIDs);

        $pageTemplateIDs = ['' => t('** Select Page Template')];
        $pageTemplates = Template::getList();
        /** @var \Concrete\Core\Entity\Page\Template $pageTemplate */
        foreach ($pageTemplates as $pageTemplate) {
            $pageTemplateIDs[$pageTemplate->getPageTemplateID()] = $pageTemplate->getPageTemplateDisplayName();
        }
        $this->set('pageTemplateIDs', $pageTemplateIDs);

        $this->set('pageSelector', $this->app->make('helper/form/page_selector'));

        $this->set('pageTitle', t('Add Batch'));

        $this->render('/dashboard/system/content_importer/batches/add_batch');
    }

    public function edit_batch_basic($id)
    {
        $this->add_batch();

        /** @var Batch $batch */
        $batch = $this->getEntry(Batch::class, (int) $id);
        if ($batch) {
            $this->set('batchID', $batch->getId());
            $this->set('name', $batch->getName());
            $this->set('sourcePath', $batch->getSourcePath());
            $this->set('documentRoot', $batch->getDocumentRoot());
            $this->set('pageTypeID', $batch->getPageTypeID());
            $this->set('pageTemplateID', $batch->getPageTemplateID());
            $this->set('parentCID', $batch->getParentCID());
            $this->set('token', $this->app->make('token'));
            $this->set('pageTitle', t('Edit Batch'));
        } else {
            $this->error->add(t('Invalid Batch.'));
            $this->view();
        }
    }

    public function submit_batch()
    {
        if (!$this->token->validate('submit_batch')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $name = $this->post('name');
        if (!$name) {
            $this->error->add(t('Please input name.'));
        }

        $sourcePath = $this->post('sourcePath');
        if (!$sourcePath) {
            $this->error->add(t('Please input source path.'));
        }

        $documentRoot = $this->post('documentRoot');
        if (!$documentRoot) {
            $this->error->add(t('Please input document root.'));
        }

        // If $sourcePath is not start with $documentRoot, show error
        if ($sourcePath && $documentRoot && strpos($sourcePath, $documentRoot) !== 0) {
            $this->error->add(t('Source path must start with document root.'));
        }

        $pageTypeID = $this->post('pageTypeID');
        if ($pageTypeID) {
            $pageType = Type::getByID($pageTypeID);
            if ($pageType) {
                if (!$this->canAddPageType($pageType)) {
                    $this->error->add(t('You need permission to add this page type.'));
                }
            } else {
                $this->error->add(t('Invalid Page Type.'));
            }
        } else {
            $this->error->add(t('Please select Page Type.'));
        }

        $pageTemplateID = $this->post('pageTemplateID');
        if (!$pageTemplateID) {
            $this->error->add(t('Please select Page Template.'));
        }

        $parentCID = $this->post('parentCID');
        if (!$parentCID) {
            $this->error->add(t('Please select Parent Page.'));
        }

        $batchID = $this->post('batchID');
        if (!$this->error->has()) {
            if ($batchID) {
                $batch = $this->getEntry(Batch::class, (int) $batchID);
            } else {
                $batch = new Batch();
            }
            $batch->setName($name);
            $batch->setSourcePath($sourcePath);
            $batch->setDocumentRoot(rtrim($documentRoot, '/'));
            $batch->setPageTypeID($pageTypeID);
            $batch->setPageTemplateID($pageTemplateID);
            $batch->setParentCID($parentCID);

            $this->entityManager->persist($batch);
            $this->entityManager->flush();

            $this->flash('success', t('Batch saved successfully.'));

            if ($batchID) {
                return $this->buildRedirect($this->action('view'));
            }

            return $this->buildRedirect($this->action('edit_batch', $batch->getId()));
        }

        if ($batchID) {
            $this->edit_batch_basic($batchID);
        } else {
            $this->add_batch();
        }
    }

    public function delete_batch()
    {
        if (!$this->token->validate('delete_batch')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $batch = $this->getEntry(Batch::class, (int) $this->post('delete_batch_id'));
        if (!$batch) {
            $this->error->add(t('Invalid Batch.'));
        }

        if (!$this->error->has()) {
            $this->entityManager->remove($batch);
            $this->entityManager->flush();

            $this->flash('success', t('Batch removed successfully.'));

            return $this->buildRedirect($this->action('view'));
        }

        $this->view();
    }

    public function copy_batch($id)
    {
        /** @var Batch $batch */
        $batch = $this->getEntry(Batch::class, (int) $id);
        if (!$batch) {
            $this->error->add(t('Invalid Batch.'));
        }

        if (!$this->error->has()) {
            $newBatch = clone $batch;
            $newBatch->setName($batch->getName() . ' ' . t('Copy'));
            $this->entityManager->persist($newBatch);
            $this->entityManager->flush();

            $this->flash('success', t('Batch copied successfully.'));

            return $this->buildRedirect($this->action('view'));
        }

        $this->view();
    }

    public function edit_batch($id)
    {
        /** @var Batch $batch */
        $batch = $this->getEntry(Batch::class, (int) $id);
        if ($batch) {
            $pageType = $batch->getPageType();
            if ($pageType) {
                if ($this->canAddPageType($pageType)) {
                    $batchItems = [];
                    foreach ($batch->getBatchItems() as $batchItem) {
                        $ptComposerFormLayoutSetControlID = $batchItem->getPtComposerFormLayoutSetControlID();
                        if ($ptComposerFormLayoutSetControlID) {
                            $batchItems[$ptComposerFormLayoutSetControlID] = $batchItem;
                        }
                    }
                    $this->set('batchItems', $batchItems);
                    $this->set('formLayoutSets', FormLayoutSet::getList($pageType));
                    $this->set('pageType', $pageType);
                    $this->set('batch', $batch);
                    $this->set('token', $this->app->make('token'));
                    $this->set('pageTitle', t('Edit %s Batch', $batch->getName()));
                    $this->render('/dashboard/system/content_importer/batches/edit_batch');
                } else {
                    $this->error->add(t('You need permission to add this page type.'));
                }
            } else {
                $this->error->add(t('Invalid Page Type.'));
            }
        } else {
            $this->error->add(t('Invalid Batch.'));
        }

        if ($this->error->has()) {
            $this->view();
        }
    }

    public function add_batch_item($batchID, $formLayoutSetControlID)
    {
        /** @var Batch|null $batch */
        $batch = $this->getEntry(Batch::class, (int) $batchID);
        $formLayoutSetControl = FormLayoutSetControl::getByID($formLayoutSetControlID);
        if ($batch && $formLayoutSetControl) {
            $this->set('batch', $batch);
            $this->set('formLayoutSetControl', $formLayoutSetControl);
            $this->set('pageTitle', t('Set Selector for %s', $formLayoutSetControl->getPageTypeComposerControlDisplayLabel()));
            $this->render('/dashboard/system/content_importer/batches/add_batch_item');
        } else {
            $this->error->add(t('Invalid Parameter.'));
            $this->view();
        }
    }

    public function edit_batch_item($batchItemID)
    {
        /** @var BatchItem|null $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $batchItemID);
        if ($batchItem) {
            $formLayoutSetControl = $batchItem->getPtComposerFormLayoutSetControl();
            if ($formLayoutSetControl) {
                $this->set('batchItem', $batchItem);
                $this->set('formLayoutSetControl', $formLayoutSetControl);
                $this->set('pageTitle', t('Edit Selector for %s', $formLayoutSetControl->getPageTypeComposerControlDisplayLabel()));
                $this->render('/dashboard/system/content_importer/batches/edit_batch_item');
            } else {
                $this->error->add(t('Composer Form Control not found.'));
                $this->view();
            }
        } else {
            $this->error->add(t('Invalid Parameter.'));
            $this->view();
        }
    }

    public function preview_batch_item()
    {
        $response = new PreviewResponse();
        if (!$this->token->validate('submit_batch_item')) {
            $response->getError()->add($this->token->getErrorMessage());
        } else {
            /** @var Batch|null $batch */
            $batch = $this->getEntry(Batch::class, (int) $this->get('batch'));
            if ($batch) {
                $sourcePath = $batch->getSourcePathArray()[0];
                /** @var Crawler $crawler */
                $crawler = $this->app->make(Crawler::class, ['sourcePath' => $sourcePath]);
                $filterType = (int) $this->get('filterType');
                $contentType = (int) $this->get('contentType');
                try {
                    $html = $crawler->getContent($filterType, $contentType, $this->get('filter'), $this->get('attribute'));
                    if ($html) {
                        $response->setResponse($html);
                    } else {
                        $response->getError()->add(t('Empty response. Please try another expression.'));
                    }
                } catch (\Exception $exception) {
                    $response->getError()->add($exception->getMessage());
                }
            } else {
                $response->getError()->add(t('Invalid Batch.'));
            }
        }

        return new JsonResponse($response);
    }

    public function submit_batch_item()
    {
        if (!$this->token->validate('submit_batch_item')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var Batch|null $batch */
        $batch = $this->getEntry(Batch::class, (int) $this->post('batch'));
        if ($batch) {
            $formLayoutSetControlID = $this->post('formLayoutSetControl');
            $filterType = $this->post('filterType');
            $filter = $this->post('filter');
            $contentType = $this->post('contentType');
            $attribute = $this->post('attribute');

            $batchItem = new BatchItem();
            $batchItemID = $this->post('batchItem');
            if ($batchItemID) {
                $batchItem = $this->getEntry(BatchItem::class, (int) $batchItemID);
            }
            if ($filterType) {
                $batchItem->setFilterType((int) $filterType);
            }
            if ($filter) {
                $batchItem->setSelector($filter);
            }
            if ($contentType) {
                $batchItem->setContentType((int) $contentType);
            }
            if ($attribute) {
                $batchItem->setAttribute($attribute);
            }
            $batchItem->setPtComposerFormLayoutSetControlID($formLayoutSetControlID);
            $batchItem->setBatch($batch);
            $batch->getBatchItems()->add($batchItem);

            $this->entityManager->persist($batch);
            $this->entityManager->persist($batchItem);
            $this->entityManager->flush();

            $this->flash('success', t('Batch Item saved successfully.'));

            return $this->buildRedirect($this->action('edit_batch', $batch->getId()));
        }
        $this->error->add(t('Invalid Batch.'));
        $this->view();
    }

    public function delete_batch_item()
    {
        if (!$this->token->validate('delete_batch_item')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $this->post('batch_item'));
        if (!$batchItem) {
            $this->error->add(t('Invalid Batch Item.'));
        }

        if (!$this->error->has() && isset($batchItem) && $batchItem) {
            $batch = $batchItem->getBatch();
            $batch->getBatchItems()->removeElement($batchItem);
            $this->entityManager->remove($batchItem);
            $this->entityManager->persist($batch);
            $this->entityManager->flush();

            $this->flash('success', t('Batch Item removed successfully.'));

            return $this->buildRedirect($this->action('edit_batch', $batch->getId()));
        }

        $this->view();
    }

    public function add_transformer($id)
    {
        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $id);
        if ($batchItem) {
            $this->set('batchItem', $batchItem);
            /** @var TransformerManager $manager */
            $manager = $this->app->make(TransformerManager::class);
            $transformer = $manager->getTransformer((string) $this->get('transformer'));
            if ($transformer) {
                $originalString = $this->getPreviewString($batchItem);
                $this->set('originalString', $originalString);
                $this->set('transformer', $transformer);
                $this->set('batchItem', $batchItem);
                $this->set('pageTitle', t('Add "%s" Transformer', $transformer->getTransformerName()));
                $this->render('/dashboard/system/content_importer/batches/edit_transformer');
            } else {
                $this->set('transformers', $manager->getTransformers());
                $this->set('pageTitle', t('Add Transformer'));
                $this->render('/dashboard/system/content_importer/batches/add_transformer');
            }
        } else {
            $this->error->add(t('Invalid Batch Item.'));
            $this->view();
        }
    }

    public function edit_transformer($id)
    {
        /** @var BatchItemTransformer $batchItemTransformer */
        $batchItemTransformer = $this->getEntry(BatchItemTransformer::class, (int) $id);
        if ($batchItemTransformer) {
            $originalString = $this->getPreviewString($batchItemTransformer->getBatchItem());
            $this->set('originalString', $originalString);
            $this->set('batchItemTransformer', $batchItemTransformer);
            $this->set('transformer', $batchItemTransformer->getClass());
            $this->set('batchItem', $batchItemTransformer->getBatchItem());
            $this->set('pageTitle', t('Edit "%s" Transformer', $batchItemTransformer->getClass()->getTransformerName()));
            $this->render('/dashboard/system/content_importer/batches/edit_transformer');
        } else {
            $this->error->add(t('Invalid Transformer.'));
            $this->view();
        }
    }

    public function submit_transformer($id)
    {
        if (!$this->token->validate('submit_transformer')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $id);
        if (!$batchItem) {
            $this->error->add(t('Invalid Batch Item.'));
        }

        /** @var TransformerManager $manager */
        $manager = $this->app->make(TransformerManager::class);
        $transformerHandleOrID = $this->post('transformer');
        if ($transformerHandleOrID) {
            if (is_numeric($transformerHandleOrID)) {
                /** @var BatchItemTransformer|null $transformerEntry */
                $transformerEntry = $this->getEntry(BatchItemTransformer::class, (int) $transformerHandleOrID);
                if ($transformerEntry) {
                    $transformer = $manager->getTransformer($transformerEntry->getClass()->getTransformerHandle());
                }
            } else {
                $transformer = $manager->getTransformer($transformerHandleOrID);
            }
        }

        if (isset($transformer) && $transformer) {
            $this->error->addError($transformer->validateRequest($this->getRequest()));
            if (!$this->error->has()) {
                if (!isset($transformerEntry) || !$transformerEntry) {
                    $transformerEntry = new BatchItemTransformer();
                    $transformers = $batchItem->getBatchItemTransformers();
                    $transformerEntry->setOrder(count($transformers) + 1);
                }
                $transformer->updateFromRequest($this->getRequest());
                $transformerEntry->setClass($transformer);
                $transformerEntry->setBatchItem($batchItem);
                $this->entityManager->persist($batchItem);
                $this->entityManager->persist($transformerEntry);
                $this->entityManager->flush();

                $this->flash('success', t('Transformer added successfully.'));

                return $this->buildRedirect($this->action('edit_batch', $batchItem->getBatch()->getId()));
            }

            $this->set('transformer', $transformer);
            $this->set('batchItem', $batchItem);
            $this->set('pageTitle', t('Edit Transformer'));
            $this->render('/dashboard/system/content_importer/batches/edit_transformer');
        } else {
            $this->error->add(t('Invalid Transformer.'));
            $this->view();
        }
    }

    public function delete_transformer()
    {
        if (!$this->token->validate('delete_transformer')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var BatchItemTransformer $batchItemTransformer */
        $batchItemTransformer = $this->getEntry(BatchItemTransformer::class, (int) $this->post('transformer'));
        if ($batchItemTransformer) {
            $batchItem = $batchItemTransformer->getBatchItem();
        } else {
            $this->error->add(t('Invalid Transformer.'));
        }

        if (!$this->error->has() && isset($batchItem) && $batchItem) {
            $batchItem = $batchItemTransformer->getBatchItem();
            $batchItem->getBatchItemTransformers()->removeElement($batchItemTransformer);
            $this->entityManager->remove($batchItemTransformer);
            $this->entityManager->persist($batchItem);
            $this->entityManager->flush();

            $this->flash('success', t('Transformer removed successfully.'));

            return $this->buildRedirect($this->action('edit_batch', $batchItem->getBatch()->getId()));
        }

        $this->view();
    }

    public function preview_transformer($id)
    {
        $response = new PreviewResponse();
        if (!$this->token->validate('submit_transformer')) {
            $response->getError()->add($this->token->getErrorMessage());
        } else {
            /** @var BatchItem|null $batchItem */
            $batchItem = $this->getEntry(BatchItem::class, (int) $id);
            if ($batchItem) {
                $transformerHandleOrID = $this->post('transformer');
                if ($transformerHandleOrID) {
                    if (is_numeric($transformerHandleOrID)) {
                        /** @var BatchItemTransformer|null $transformerEntry */
                        $transformerEntry = $this->getEntry(BatchItemTransformer::class, (int) $transformerHandleOrID);
                        if ($transformerEntry) {
                            $transformer = $transformerEntry->getClass();
                        }
                    } else {
                        /** @var TransformerManager $manager */
                        $manager = $this->app->make(TransformerManager::class);
                        $transformer = $manager->getTransformer($transformerHandleOrID);
                    }
                }
                if (isset($transformer) && $transformer) {
                    $error = $transformer->validateRequest($this->getRequest());
                    if (!$error->has()) {
                        $transformer->updateFromRequest($this->getRequest());
                        $html = (string) $this->post('original');
                        $result = $transformer->transform($html);
                        $response->setResponse($result);
                    } else {
                        $response->getError()->addError($error);
                    }
                } else {
                    $response->getError()->add(t('Invalid Transformer.'));
                }
            } else {
                $response->getError()->add(t('Invalid Batch Item.'));
            }
        }

        return new JsonResponse($response);
    }

    public function submit_transformer_order($id)
    {
        if (!$this->token->validate('order_transformers')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $id);
        if (!$batchItem) {
            $this->error->add(t('Invalid Batch Item.'));
        }

        if ($batchItem && !$this->error->has()) {
            $transformerIDs = $this->post('transformerOrder');
            foreach ($transformerIDs as $index => $transformerID) {
                /** @var BatchItemTransformer $transformer */
                $transformer = $this->getEntry(BatchItemTransformer::class, $transformerID);
                $transformer->setOrder($index + 1);
                $this->entityManager->persist($transformer);
            }
            $this->entityManager->flush();

            $this->flash('success', t('Transformers reordered successfully.'));

            return $this->buildRedirect($this->action('edit_batch', $batchItem->getBatch()->getId()));
        }
        $this->view();
    }

    public function order_transformers($id)
    {
        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, (int) $id);
        if ($batchItem) {
            $this->set('batchItem', $batchItem);
            $this->set('transformers', $batchItem->getBatchItemTransformers());
            $this->set('pageTitle', t('Order Transformers'));
            $this->render('/dashboard/system/content_importer/batches/order_transformers');
        } else {
            $this->error->add(t('Invalid Batch Item.'));
            $this->view();
        }
    }

    public function import_batch()
    {
        if (!$this->token->validate('import_batch')) {
            $this->error->add($this->token->getErrorMessage());
        }

        /** @var Batch $batch */
        $batch = $this->getEntry(Batch::class, (int) $this->post('batch_id'));
        if (!$batch) {
            $this->error->add(t('Invalid Parameter.'));
        }

        $pageType = $batch->getPageType();
        if ($pageType) {
            if (!$this->canAddPageType($pageType)) {
                $this->error->add(t('You need permission to add %s page type.', $pageType->getPageTypeDisplayName()));
            }
        } else {
            $this->error->add(t('Invalid Page Type.'));
        }

        if (!$this->error->has()) {
            $skipImported = $this->post('skip_imported');
            /** @var ImportBatchLogRepository $logRepository */
            $logRepository = $this->entityManager->getRepository(ImportBatchLog::class);
            /** @var LoggerFactory $loggerFactory */
            $loggerFactory = $this->app->make(LoggerFactory::class);
            $logger = $loggerFactory->createLogger('content_importer');
            $commandBatch = \Concrete\Core\Command\Batch\Batch::create(t('Import pages'), function () use ($batch, $logRepository, $skipImported, $logger) {
                foreach ($batch->getSourcePathArray() as $sourcePath) {
                    $shouldSkip = false;
                    if ($skipImported) {
                        /** @var ImportBatchLog $log */
                        $log = $logRepository->findOneByOriginal($sourcePath);
                        if ($log) {
                            $shouldSkip = true;
                            $logger->info(t('Importing %s skipped. Already imported at %s', $sourcePath, $log->getImportedPage()->getCollectionLink()));
                        }
                    }
                    if (!$shouldSkip) {
                        $command = new ImportBatchCommand();
                        $command->setBatchID($batch->getId());
                        $command->setSourcePath($sourcePath);
                        yield $command;
                    }
                }
            });

            return $this->dispatchBatch($commandBatch);
        }

        return $this->app->make(ResponseFactoryInterface::class)->error($this->error);
    }

    public function import_completed()
    {
        $this->set('message', t('Import Completed.'));
        $this->view();
    }

    private function getPreviewString(BatchItem $batchItem): string
    {
        $batch = $batchItem->getBatch();
        $sourcePath = $batch->getSourcePathArray()[0];
        /** @var Crawler $crawler */
        $crawler = $this->app->make(Crawler::class, ['sourcePath' => $sourcePath]);

        return $crawler->getContent($batchItem->getFilterType(), $batchItem->getContentType(), $batchItem->getSelector(), $batchItem->getAttribute());
    }
}
