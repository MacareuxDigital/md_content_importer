<?php

namespace Macareux\ContentImporter\Publisher\Block;

class BlockPublisherManager
{
    /**
     * @var BlockPublisherInterface[]
     */
    private $publishers = [];

    public function registerPublisher(BlockPublisherInterface $blockPublisher)
    {
        $this->publishers[] = $blockPublisher;
    }

    /**
     * @return array
     */
    public function getPublishers(): array
    {
        return $this->publishers;
    }
}
