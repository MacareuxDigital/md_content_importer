<?php

namespace Macareux\ContentImporter\Entity;

use Concrete\Core\Entity\File\File as FileEntity;
use Concrete\Core\File\File;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mdContentImporterFileImportLog")
 */
class ImportFileLog
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

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
    private $imported_fID;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getImportDate(): ?\DateTimeImmutable
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
    public function getOriginal(): ?string
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
    public function getImportedFID(): ?int
    {
        return $this->imported_fID;
    }

    public function getImportedFile(): ?FileEntity
    {
        return File::getByID($this->getImportedFID());
    }

    /**
     * @param int $imported_fID
     */
    public function setImportedFID(int $imported_fID): void
    {
        $this->imported_fID = $imported_fID;
    }

    /**
     * @param FileEntity $file
     *
     * @return void
     */
    public function setImportedFile(FileEntity $file)
    {
        $this->setImportedFID($file->getFileID());
    }
}
