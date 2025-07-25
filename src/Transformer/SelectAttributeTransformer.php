<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Macareux\ContentImporter\Entity\BatchItem;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Transformer to generate text representation for topics attribute. e.g. "tid:1|tid:2|tid:3".
 */
class SelectAttributeTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Select Attribute');
    }

    public function getTransformerDescription(): string
    {
        return t('Import select attribute value. Input should be a HTML content containing topic labels.');
    }

    public function getTransformerHandle(): string
    {
        return 'select_attribute';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        $options = [];
        $crawler = new Crawler($input);

        /** @var \DOMElement $child */
        foreach ($crawler->filter('body')->children() as $child) {
            $options[] = $child->textContent;
        }

        return implode("\n", $options);
    }

    public function renderForm(BatchItem $batchItem): void
    {
        // No options
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function updateFromRequest(Request $request): void
    {
        // No options
    }
}
