<?php

namespace Macareux\ContentImporter\Entity;

use Concrete\Core\Page\Page;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Macareux\ContentImporter\Repository\ImportBatchLogRepository")
 * @ORM\Table(name="mdContentImporterImportLog")
 */
class ImportBatchLog
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Batch|null
     * @ORM\ManyToOne(targetEntity="Batch")
     * @ORM\JoinColumn(name="batch_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $batch = null;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $import_date;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $original;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $imported_cID;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Batch|null
     */
    public function getBatch(): ?Batch
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
     * @return \DateTimeImmutable
     */
    public function getImportDate(): \DateTimeImmutable
    {
        return $this->import_date;
    }

    /**
     * @param \DateTimeImmutable $import_date
     */
    public function setImportDate(\DateTimeImmutable $import_date): void
    {
        $this->import_date = $import_date;
    }

    /**
     * @return string
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * @param string $original
     */
    public function setOriginal(string $original): void
    {
        $this->original = $original;
    }

    /**
     * @return int
     */
    public function getImportedCID(): int
    {
        return $this->imported_cID;
    }

    public function getImportedPage(): ?Page
    {
        $page = Page::getByID($this->getImportedCID());
        if ($page && !$page->isError()) {
            return $page;
        }

        return null;
    }

    /**
     * @param int $imported_cID
     */
    public function setImportedCID(int $imported_cID): void
    {
        $this->imported_cID = $imported_cID;
    }

    /**
     * @param Page $page
     * @return void
     */
    public function setImportedPage(Page $page): void
    {
        $this->setImportedCID($page->getCollectionID());
    }
}