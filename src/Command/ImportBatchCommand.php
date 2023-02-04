<?php

namespace Macareux\ContentImporter\Command;

use Concrete\Core\Foundation\Command\Command;

class ImportBatchCommand extends Command
{
    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var int
     */
    protected $batchID;

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     */
    public function setSourcePath(string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
    }

    /**
     * @return int|null
     */
    public function getBatchID(): int
    {
        return $this->batchID;
    }

    /**
     * @param int|null $batchID
     */
    public function setBatchID(int $batchID): void
    {
        $this->batchID = $batchID;
    }
}
