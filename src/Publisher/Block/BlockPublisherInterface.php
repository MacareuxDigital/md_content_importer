<?php

namespace Macareux\ContentImporter\Publisher\Block;

use Concrete\Core\Entity\Block\BlockType\BlockType;

interface BlockPublisherInterface
{
    /**
     * @param BlockType $blockType
     * @param string $content
     * @param array $data
     *
     * @return array $data
     */
    public function publish(BlockType $blockType, string $content, array $data): array;
}
