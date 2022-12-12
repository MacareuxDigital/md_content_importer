<?php

namespace Macareux\ContentImporter\Entity;

use Doctrine\ORM\Mapping as ORM;
use Macareux\ContentImporter\Transformer\TransformerInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="mdContentImporterBatchItemTransformers")
 */
class BatchItemTransformer
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var TransformerInterface
     * @ORM\Column(type="object")
     */
    private $class;

    /**
     * @var BatchItem
     * @ORM\ManyToOne(targetEntity="BatchItem", inversedBy="batchItemTransformers")
     * @ORM\JoinColumn(name="batchItemID", referencedColumnName="id")
     */
    private $batchItem;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return TransformerInterface
     */
    public function getClass(): TransformerInterface
    {
        return $this->class;
    }

    /**
     * @param TransformerInterface $class
     */
    public function setClass(TransformerInterface $class): void
    {
        $this->class = $class;
    }

    /**
     * @return BatchItem|null
     */
    public function getBatchItem(): ?BatchItem
    {
        return $this->batchItem;
    }

    /**
     * @param BatchItem $batchItem
     */
    public function setBatchItem(BatchItem $batchItem): void
    {
        $this->batchItem = $batchItem;
    }
}
