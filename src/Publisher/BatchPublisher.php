<?php

namespace Macareux\ContentImporter\Publisher;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Attribute\SimpleTextExportableAttributeInterface;
use Concrete\Core\Entity\Attribute\Key\Key;
use Concrete\Core\Entity\Block\BlockType\BlockType;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Page\Type\Composer\Control\BlockControl;
use Concrete\Core\Page\Type\Composer\Control\CollectionAttributeControl;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\DateTimeCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\DescriptionCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\NameCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\UrlSlugCorePageProperty;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Http\Crawler;
use Psr\Log\LoggerInterface;

class BatchPublisher implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    /**
     * @var Batch
     */
    protected $batch;

    /**
     * @var ErrorList
     */
    protected $error;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Batch $batch
     */
    public function __construct(Batch $batch, ErrorList $error, LoggerFactory $factory)
    {
        $this->batch = $batch;
        $this->error = $error;
        $this->logger = $factory->createLogger('content_importer');
    }

    public function publish(string $sourcePath)
    {
        $parent = $this->batch->getParentPage();
        $pageType = $this->batch->getPageType();
        $pageTemplate = $this->batch->getPageTemplate();
        if (!$parent->isError() && $pageType && $pageTemplate) {
            $page = $pageType->createDraft($pageTemplate);
            $page->setPageDraftTargetParentPageID($parent->getCollectionID());

            $data = [];
            foreach ($this->batch->getBatchItems() as $batchItem) {
                $formLayoutSetControl = $batchItem->getPtComposerFormLayoutSetControl();
                if ($formLayoutSetControl) {
                    $composerControlObject = $formLayoutSetControl->getPageTypeComposerControlObject();
                    if ($composerControlObject instanceof NameCorePageProperty) {
                        $data['cName'] = $this->getTransformedString($batchItem, $sourcePath);
                    }
                    if ($composerControlObject instanceof DateTimeCorePageProperty) {
                        $data['cDatePublic'] = $this->getTransformedString($batchItem, $sourcePath);
                    }
                    if ($composerControlObject instanceof DescriptionCorePageProperty) {
                        $data['cDescription'] = $this->getTransformedString($batchItem, $sourcePath);
                    }
                    if ($composerControlObject instanceof UrlSlugCorePageProperty) {
                        $data['cHandle'] = $this->getTransformedString($batchItem, $sourcePath);
                    }
                }
            }
            $page->update($data);

            foreach ($this->batch->getBatchItems() as $batchItem) {
                $formLayoutSetControl = $batchItem->getPtComposerFormLayoutSetControl();
                if ($formLayoutSetControl) {
                    $content = $this->getTransformedString($batchItem, $sourcePath);
                    $composerControlObject = $formLayoutSetControl->getPageTypeComposerControlObject();
                    if ($composerControlObject instanceof BlockControl) {
                        /** @var BlockType $bt */
                        $bt = $composerControlObject->getBlockTypeObject();
                        $blockRequest = $this->createBlockRequest($bt, $content);
                        $composerControlObject->publishToPage($page, $blockRequest, []);
                    }
                    if ($composerControlObject instanceof CollectionAttributeControl) {
                        /** @var Key $ak */
                        $ak = $composerControlObject->getAttributeKeyObject();
                        $akc = $ak->getController();
                        if ($akc instanceof SimpleTextExportableAttributeInterface) {
                            $initialValueObject = $page->getAttributeValueObject($ak);
                            $akc->setAttributeValue($initialValueObject);
                            $newValueObject = $akc->updateAttributeValueFromTextRepresentation($content, $this->error);
                            if ($initialValueObject !== $newValueObject) {
                                $page->setAttribute($ak, $newValueObject);
                            }
                        }
                    }
                }
            }

            $pageType->publish($page);
        } else {
            $this->error->add(t('Failed to start importing.'));
        }

        if ($this->error->has()) {
            $this->logger->warning($this->error->toText());
        }
    }

    public function getTransformedString(BatchItem $batchItem, string $sourcePath): string
    {
        /** @var Crawler $crawler */
        $crawler = $this->app->make(Crawler::class, ['sourcePath' => $sourcePath]);
        $content = $crawler->getContent($batchItem->getFilterType(), $batchItem->getContentType(), $batchItem->getSelector(), $batchItem->getAttribute());

        foreach ($batchItem->getBatchItemTransformers() as $batchItemTransformer) {
            $transformer = $batchItemTransformer->getClass();
            $content = $transformer->transform($content);
        }

        return $content;
    }

    protected function createBlockRequest(BlockType $blockType, string $content): array
    {
        $data = [];
        switch ($blockType->getBlockTypeHandle()) {
            case 'content':
            default:
                $data['content'] = $content;
                break;
        }

        return $data;
    }
}
