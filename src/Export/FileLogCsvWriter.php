<?php

namespace Macareux\ContentImporter\Export;

use Doctrine\ORM\EntityManager;
use League\Csv\Writer;
use Macareux\ContentImporter\Entity\ImportFileLog;
use Macareux\ContentImporter\Search\ImportBatchLogList;
use Macareux\ContentImporter\Search\ImportFileLogList;

class FileLogCsvWriter
{
    /**
     * @var Writer The writer we use to output
     */
    protected $writer;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param Writer $writer
     * @param EntityManager $entityManager
     */
    public function __construct(Writer $writer, EntityManager $entityManager)
    {
        $this->writer = $writer;
        $this->entityManager = $entityManager;
    }

    public function insertHeaders()
    {
        $this->writer->insertOne(iterator_to_array($this->getHeaders()));
    }

    public function insertEntryList(ImportBatchLogList $list)
    {
        $list = clone $list;
        foreach ($this->getEntries($list) as $entry) {
            $this->writer->insertOne(iterator_to_array($this->getRecord($entry)));
        }
    }

    private function getEntries(ImportFileLogList $list)
    {
        foreach ($list->getResults() as $result) {
            yield $result;
        }
    }

    private function getRecord(ImportFileLog $record)
    {
        $f = $record->getImportedFile();
        yield $record->getOriginal();
        yield $f ? $f->getFileID() : t('Not Found');
        yield $f ? $f->getApprovedVersion()->getFileName() : t('Not Found');
        yield $record->getImportDate()->format('Y-m-d H:i:s');
    }

    private function getHeaders()
    {
        yield t('Original');
        yield t('File ID');
        yield t('File Name');
        yield t('Import Date');
    }
}
