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
    public const TYPE_XPATH = 1;

    public const TYPE_SELECTOR = 2;

    public const TYPE_FILENAME = 3;

    public const TYPE_FILEPATH = 4;

    public const CONTENT_HTML = 10;

    public const CONTENT_TEXT = 20;

    public const CONTENT_ATTRIBUTE = 30;

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
    private $selector;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    private $filterType = self::TYPE_XPATH;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    private $contentType = self::CONTENT_HTML;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $attribute;

    /**
     * @var Batch
     * @ORM\ManyToOne(targetEntity="Batch", inversedBy="batchItems")
     * @ORM\JoinColumn(name="batchID", referencedColumnName="id")
     */
    private $batch;

    /**
     * @var Collection<BatchItemTransformer>
     * @ORM\OneToMany(targetEntity="BatchItemTransformer", mappedBy="batchItem", cascade={"persist", "remove"})
     * @ORM\OrderBy({"orderIndex" = "ASC"})
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
     * @return int
     */
    public function getFilterType(): int
    {
        return $this->filterType;
    }

    /**
     * @param int $filterType
     */
    public function setFilterType(int $filterType): void
    {
        $this->filterType = $filterType;
    }

    /**
     * @return int
     */
    public function getContentType(): int
    {
        return $this->contentType;
    }

    /**
     * @param int $contentType
     */
    public function setContentType(int $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string|null
     */
    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     */
    public function setAttribute(string $attribute): void
    {
        $this->attribute = $attribute;
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
