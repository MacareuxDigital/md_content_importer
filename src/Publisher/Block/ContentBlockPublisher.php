<?php

namespace Macareux\ContentImporter\Publisher\Block;

use Concrete\Core\Entity\Block\BlockType\BlockType;

class ContentBlockPublisher implements BlockPublisherInterface
{
    public function publish(BlockType $blockType, string $content, array $data): array
    {
        if ($blockType->getBlockTypeHandle() === 'content') {
            $data['content'] = $content;
        }

        return $data;
    }
}
