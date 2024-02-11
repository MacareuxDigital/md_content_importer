<?php

namespace Macareux\ContentImporter\Publisher\Block;

use Concrete\Core\Entity\Block\BlockType\BlockType;

interface BlockPublisherInterface
{
    /**
     * @param BlockType $blockType The block type to publish. You can use this to check if the block type is the one you want to publish.
     * @param string $content The extracted content from the source.
     * @param array $data The data to pass `save()` method of the block type controller.
     *
     * @return array $data
     */
    public function publish(BlockType $blockType, string $content, array $data): array;
}
