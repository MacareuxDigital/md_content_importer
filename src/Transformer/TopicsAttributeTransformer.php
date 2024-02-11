<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Tree\Node\Type\Topic;
use Macareux\ContentImporter\Entity\BatchItem;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Transformer to generate text representation for topics attribute. e.g. "tid:1|tid:2|tid:3".
 */
class TopicsAttributeTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Topics Attribute');
    }

    public function getTransformerDescription(): string
    {
        return t('Import topics and return the topic IDs for topics attribute. Input should be a HTML content containing topic labels.');
    }

    public function getTransformerHandle(): string
    {
        return 'topics_attribute';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        $topics = [];
        $crawler = new Crawler($input);

        /** @var \DOMElement $child */
        foreach ($crawler->filter('body')->children() as $child) {
            $topicName = $child->textContent;
            $topic = Topic::getNodeByName($topicName);
            if ($topic) {
                $topics[] = 'tid:' . $topic->getTreeNodeID();
            }
        }

        return implode('|', $topics);
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
