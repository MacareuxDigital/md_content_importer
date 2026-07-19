<?php

namespace Macareux\ContentImporter\Command;

use Concrete\Core\Support\Facade\Application;
use Macareux\ContentImporter\Csv\UrlCsvImporter;

class ImportUrlCsvChunkCommandHandler
{
    public function __invoke(ImportUrlCsvChunkCommand $command)
    {
        $app = Application::getFacadeApplication();
        /** @var UrlCsvImporter $importer */
        $importer = $app->make(UrlCsvImporter::class);
        $importer->upsertRows($command->getRows());
    }
}
