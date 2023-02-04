<?php

namespace Macareux\ContentImporter\Command;

use Carbon\Carbon;
use Concrete\Core\Foundation\Command\Command;

class ImportListItemCommand extends Command
{
    /**
     * @var string|null
     */
    private $title;

    /**
     * @var \DateTime|string|null
     */
    private $date_time;

    /**
     * @var string|null
     */
    private $link;

    /**
     * @var string|null
     */
    private $topic;

    /**
     * @var string|null
     */
    private $topic_handle;

    /**
     * @var int|null
     */
    private $parentID;

    /**
     * @var int|null
     */
    private $typeID;

    /**
     * @var int|null
     */
    private $templateID;

    /**
     * @var string|null
     */
    private $file_handle;

    /**
     * @var int|null
     */
    private $folderID;

    /**
     * @var string|null
     */
    private $external_url_handle;

    /**
     * @var string|null
     */
    private $document_root;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTime(): ?\DateTime
    {
        return $this->date_time;
    }

    /**
     * @param \DateTime|string $date_time
     */
    public function setDateTime($date_time): void
    {
        if (!$date_time instanceof \DateTimeInterface) {
            $date_time = Carbon::createFromFormat(\DateTime::ATOM, $date_time);
        }
        $this->date_time = $date_time;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string|null
     */
    public function getTopic(): ?string
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @return string|null
     */
    public function getTopicHandle(): ?string
    {
        return $this->topic_handle;
    }

    /**
     * @param string $topic_handle
     */
    public function setTopicHandle(string $topic_handle): void
    {
        $this->topic_handle = $topic_handle;
    }

    /**
     * @return int|null
     */
    public function getParentID(): ?int
    {
        return $this->parentID;
    }

    /**
     * @param int $parentID
     */
    public function setParentID(int $parentID): void
    {
        $this->parentID = $parentID;
    }

    /**
     * @return int|null
     */
    public function getTypeID(): ?int
    {
        return $this->typeID;
    }

    /**
     * @param int $typeID
     */
    public function setTypeID(int $typeID): void
    {
        $this->typeID = $typeID;
    }

    /**
     * @return int|null
     */
    public function getTemplateID(): ?int
    {
        return $this->templateID;
    }

    /**
     * @param int $templateID
     */
    public function setTemplateID(int $templateID): void
    {
        $this->templateID = $templateID;
    }

    /**
     * @return string|null
     */
    public function getFileHandle(): ?string
    {
        return $this->file_handle;
    }

    /**
     * @param string $file_handle
     */
    public function setFileHandle(string $file_handle): void
    {
        $this->file_handle = $file_handle;
    }

    /**
     * @return int|null
     */
    public function getFolderID(): ?int
    {
        return $this->folderID;
    }

    /**
     * @param int $folderID
     */
    public function setFolderID(int $folderID): void
    {
        $this->folderID = $folderID;
    }

    /**
     * @return string|null
     */
    public function getExternalUrlHandle(): ?string
    {
        return $this->external_url_handle;
    }

    /**
     * @param string $external_url_handle
     */
    public function setExternalUrlHandle(string $external_url_handle): void
    {
        $this->external_url_handle = $external_url_handle;
    }

    /**
     * @return string|null
     */
    public function getDocumentRoot(): ?string
    {
        return $this->document_root;
    }

    /**
     * @param string $document_root
     */
    public function setDocumentRoot(string $document_root): void
    {
        $this->document_root = $document_root;
    }
}
