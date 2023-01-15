<?php

namespace Macareux\ContentImporter\Export;

use Doctrine\ORM\EntityManager;
use League\Csv\Writer;
use Macareux\ContentImporter\Entity\ImportBatchLog;
use Macareux\ContentImporter\Search\ImportBatchLogList;

class CsvWriter
{
    /** @var Writer The writer we use to output */
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

    private function getEntries(ImportBatchLogList $list)
    {
        foreach ($list->getResults() as $result) {
            yield $result;
        }
    }

    private function getRecord(ImportBatchLog $record)
    {
        $batch = $record->getBatch();
        $imported = $record->getImportedPage();
        yield $batch ? $batch->getName() : t('Deleted');
        yield $record->getOriginal();
        yield $imported ? $imported->getCollectionPath() : t('Not Found');
        yield $record->getImportDate()->format('Y-m-d H:i:s');
    }

    private function getHeaders()
    {
        yield t('Batch');
        yield t('Original');
        yield t('Imported');
        yield t('Import Date');
    }
}