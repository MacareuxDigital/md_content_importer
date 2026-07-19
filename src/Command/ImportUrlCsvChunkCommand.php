<?php

namespace Macareux\ContentImporter\Command;

use Concrete\Core\Foundation\Command\Command;

class ImportUrlCsvChunkCommand extends Command
{
    /**
     * @var array{url: string, title: ?string, metadata: array}[]
     */
    protected $rows = [];

    /**
     * @return array{url: string, title: ?string, metadata: array}[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param array{url: string, title: ?string, metadata: array}[] $rows
     */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }
}
