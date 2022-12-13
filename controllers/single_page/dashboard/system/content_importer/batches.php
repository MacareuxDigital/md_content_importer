<?php /** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter;

use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Composer\FormLayoutSet;
use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Concrete\Core\Page\Type\Type;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Entity\BatchItemTransformer;
use Macareux\ContentImporter\Http\Crawler;
use Macareux\ContentImporter\Http\PreviewResponse;
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
        $this->set('batches', $this->getAll(Batch::class));

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

        if (!$this->error->has()) {
            $batch = new Batch();
            $batch->setName($name);
            $batch->setSourcePath($sourcePath);
            $batch->setPageTypeID($pageTypeID);
            $batch->setPageTemplateID($pageTemplateID);
            $batch->setParentCID($parentCID);

            $this->entityManager->persist($batch);
            $this->entityManager->flush();

            $this->flash('success', t('Batch saved successfully.'));

            return $this->buildRedirect($this->action('edit_batch', $batch->getId()));
        }

        $this->view();
    }

    public function edit_batch($id)
    {
        /** @var Batch $batch */
        $batch = $this->getEntry(Batch::class, $id);
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
                    $this->set('pageTitle', t('Edit Batch'));
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
        $batch = $this->getEntry(Batch::class, $batchID);
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
        $batchItem = $this->getEntry(BatchItem::class, $batchItemID);
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
            $batch = $this->getEntry(Batch::class, $this->get('batch'));
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
        $batch = $this->getEntry(Batch::class, $this->post('batch'));
        if ($batch) {
            $formLayoutSetControlID = $this->post('formLayoutSetControl');
            $filterType = $this->post('filterType');
            $filter = $this->post('filter');
            $contentType = $this->post('contentType');
            $attribute = $this->post('attribute');

            $batchItem = new BatchItem();
            $batchItemID = $this->post('batchItem');
            if ($batchItemID) {
                $batchItem = $this->getEntry(BatchItem::class, $batchItemID);
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
        } else {
            $this->error->add(t('Invalid Batch.'));
            $this->view();
        }
    }

    public function add_transformer($id)
    {
        /** @var BatchItem $batchItem */
        $batchItem = $this->getEntry(BatchItem::class, $id);
        if ($batchItem) {
            $this->set('batchItem', $batchItem);
            /** @var TransformerManager $manager */
            $manager = $this->app->make(TransformerManager::class);
            $transformer = $manager->getTransformer((string)$this->get('transformer'));
            if ($transformer) {
                $originalString = $this->getPreviewString($batchItem);
                $this->set('originalString', $originalString);
                $this->set('transformer', $transformer);
                $this->set('batchItem', $batchItem);
                $this->set('pageTitle', t('Edit Transformer'));
                $this->render('/dashboard/system/content_importer/batches/edit_transformer');
            } else {
                $transformers = [];
                foreach ($manager->getTransformers() as $transformer) {
                    $transformers[$transformer->getTransformerHandle()] = $transformer->getTransformerName();
                }
                $this->set('transformers', $transformers);
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
        $batchItemTransformer = $this->getEntry(BatchItemTransformer::class, $id);
        if ($batchItemTransformer) {
            $originalString = $this->getPreviewString($batchItemTransformer->getBatchItem());
            $this->set('originalString', $originalString);
            $this->set('batchItemTransformer', $batchItemTransformer);
            $this->set('transformer', $batchItemTransformer->getClass());
            $this->set('batchItem', $batchItemTransformer->getBatchItem());
            $this->set('pageTitle', t('Edit Transformer'));
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
        $batchItem = $this->getEntry(BatchItem::class, $id);
        if (!$batchItem) {
            $this->error->add(t('Invalid Batch Item.'));
        }

        $transformerHandleOrID = $this->post('transformer');
        if ($transformerHandleOrID) {
            if (is_numeric($transformerHandleOrID)) {
                /** @var BatchItemTransformer|null $transformerEntry */
                $transformerEntry = $this->getEntry(BatchItemTransformer::class, $transformerHandleOrID);
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
            $this->error->addError($transformer->validateRequest($this->getRequest()));
            if (!$this->error->has()) {
                if (!isset($transformerEntry) || !$transformerEntry) {
                    $transformerEntry = new BatchItemTransformer();
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
        $batchItemTransformer = $this->getEntry(BatchItemTransformer::class, $this->post('transformer'));
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
            $batchItem = $this->getEntry(BatchItem::class, $id);
            if ($batchItem) {
                $transformerHandleOrID = $this->post('transformer');
                if ($transformerHandleOrID) {
                    if (is_numeric($transformerHandleOrID)) {
                        /** @var BatchItemTransformer|null $transformerEntry */
                        $transformerEntry = $this->getEntry(BatchItemTransformer::class, $transformerHandleOrID);
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
                        $html = $this->post('original');
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

    private function getPreviewString(BatchItem $batchItem): string
    {
        $html = '';
        $batch = $batchItem->getBatch();
        $sourcePath = $batch->getSourcePathArray()[0];
        /** @var Crawler $crawler */
        $crawler = $this->app->make(Crawler::class, ['sourcePath' => $sourcePath]);

        return $crawler->getContent($batchItem->getFilterType(), $batchItem->getContentType(), $batchItem->getSelector(), $batchItem->getAttribute());
    }
}