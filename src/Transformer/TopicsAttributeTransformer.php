<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Tree\Node\Type\Topic;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Transformer to generate text representation for topics attribute. e.g. "tid:1|tid:2|tid:3"
 */
class TopicsAttributeTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Topics Attribute');
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
        foreach ($crawler->children() as $child) {
            $topicName = $child->textContent;
            $topic = Topic::getNodeByName($topicName);
            if ($topic) {
                $topics[] = 'tid:' . $topic->getTreeNodeID();
            }
        }

        return implode('|', $topics);
    }

    public function renderForm(): void
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