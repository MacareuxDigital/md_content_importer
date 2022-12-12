<?php

namespace Macareux\ContentImporter\Entity;

use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mdContentImporterBatchItems")
 */
class BatchItem
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ptComposerFormLayoutSetControlID;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $xpath;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $selector;

    /**
     * @var Batch
     * @ORM\ManyToOne(targetEntity="Batch", inversedBy="batchItems")
     * @ORM\JoinColumn(name="batchID", referencedColumnName="id")
     */
    private $batch;

    /**
     * @var Collection<BatchItemTransformer>
     * @ORM\OneToMany(targetEntity="BatchItemTransformer", mappedBy="batchItem", cascade={"persist", "remove"})
     */
    private $batchItemTransformers;

    public function __construct()
    {
        $this->batchItemTransformers = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPtComposerFormLayoutSetControlID(): ?int
    {
        return $this->ptComposerFormLayoutSetControlID;
    }

    /**
     * @return FormLayoutSetControl|null
     */
    public function getPtComposerFormLayoutSetControl(): ?FormLayoutSetControl
    {
        return FormLayoutSetControl::getByID($this->getPtComposerFormLayoutSetControlID());
    }

    /**
     * @param int|null $ptComposerFormLayoutSetControlID
     */
    public function setPtComposerFormLayoutSetControlID(int $ptComposerFormLayoutSetControlID): void
    {
        $this->ptComposerFormLayoutSetControlID = $ptComposerFormLayoutSetControlID;
    }

    /**
     * @return string|null
     */
    public function getXpath(): ?string
    {
        return $this->xpath;
    }

    /**
     * @param string $xpath
     */
    public function setXpath(string $xpath): void
    {
        $this->xpath = $xpath;
    }

    /**
     * @return string|null
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }

    /**
     * @param string $selector
     */
    public function setSelector(string $selector): void
    {
        $this->selector = $selector;
    }

    /**
     * @return Batch
     */
    public function getBatch(): Batch
    {
        return $this->batch;
    }

    /**
     * @param Batch $batch
     */
    public function setBatch(Batch $batch): void
    {
        $this->batch = $batch;
    }

    /**
     * @return Collection<BatchItemTransformer>
     */
    public function getBatchItemTransformers()
    {
        return $this->batchItemTransformers;
    }
}
