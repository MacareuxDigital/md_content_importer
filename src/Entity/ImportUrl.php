<?php

namespace Macareux\ContentImporter\Entity;

use Concrete\Core\Page\Page;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Macareux\ContentImporter\Repository\ImportUrlRepository")
 * @ORM\Table(
 *     name="mdContentImporterUrls",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="mdContentImporterUrlsUrl", columns={"url"})
 *     }
 * )
 */
class ImportUrl
{
    public const STATUS_NOT_YET = 'not_yet';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IMPORTED = 'imported';

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=767)
     */
    private $url = '';

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var Batch|null
     * @ORM\ManyToOne(targetEntity="Batch")
     * @ORM\JoinColumn(name="batch_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $batch;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $importedCID;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $importDate;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $dateAdded;

    /**
     * @var array|null
     * @ORM\Column(type="json", nullable=true)
     */
    private $metadata;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Batch|null
     */
    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    /**
     * @param Batch|null $batch
     */
    public function setBatch(?Batch $batch): void
    {
        $this->batch = $batch;
    }

    /**
     * @return int|null
     */
    public function getImportedCID(): ?int
    {
        return $this->importedCID;
    }

    /**
     * @param int|null $importedCID
     */
    public function setImportedCID(?int $importedCID): void
    {
        $this->importedCID = $importedCID;
    }

    public function getImportedPage(): ?Page
    {
        if (!$this->importedCID) {
            return null;
        }

        $page = Page::getByID($this->importedCID);
        if ($page && !$page->isError()) {
            return $page;
        }

        return null;
    }

    /**
     * @param Page $page
     */
    public function setImportedPage(Page $page): void
    {
        $this->setImportedCID($page->getCollectionID());
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getImportDate(): ?\DateTimeImmutable
    {
        return $this->importDate;
    }

    /**
     * @param \DateTimeImmutable|null $importDate
     */
    public function setImportDate(?\DateTimeImmutable $importDate): void
    {
        $this->importDate = $importDate;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateAdded(): \DateTimeImmutable
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTimeImmutable $dateAdded
     */
    public function setDateAdded(\DateTimeImmutable $dateAdded): void
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function isManuallyImported(): bool
    {
        return ($this->metadata['manual_import'] ?? false) === true;
    }

    public function setManuallyImported(bool $manuallyImported): void
    {
        $metadata = $this->metadata ?: [];
        if ($manuallyImported) {
            $metadata['manual_import'] = true;
        } else {
            unset($metadata['manual_import']);
        }
        $this->metadata = $metadata ?: null;
    }

    public function getStatus(): string
    {
        if ($this->getImportedPage()) {
            return self::STATUS_IMPORTED;
        }

        if ($this->batch) {
            return self::STATUS_ASSIGNED;
        }

        return self::STATUS_NOT_YET;
    }

    public function getStatusLabel(): string
    {
        switch ($this->getStatus()) {
            case self::STATUS_IMPORTED:
                return t('Imported');
            case self::STATUS_ASSIGNED:
                return t('Assigned to batch');
            default:
                return t('Not yet');
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_NOT_YET => t('Not yet'),
            self::STATUS_ASSIGNED => t('Assigned to batch'),
            self::STATUS_IMPORTED => t('Imported'),
        ];
    }
}
