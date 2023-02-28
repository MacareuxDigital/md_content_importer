<?php

namespace Macareux\ContentImporter\Command;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Concrete\Core\File\Import\ImportException;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Support\Facade\Application;
use Macareux\ContentImporter\Traits\FileImporterTrait;

class ImportListItemCommandHandler
{
    use FileImporterTrait;

    public function __invoke(ImportListItemCommand $command)
    {
        $app = Application::getFacadeApplication();
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $app->make(LoggerFactory::class);
        $logger = $loggerFactory->createLogger('importer');
        $logger->info(sprintf('Start importing %s...', $command->getTitle()));
        $logger->debug(var_export($command, true));

        $data = ['cName' => $command->getTitle()];
        if ($command->getDateTime() && $command->getDateFormat()) {
            try {
                $dateTime = Carbon::createFromFormat($command->getDateFormat(), $command->getDateTime());
                $data['cDatePublic'] = $dateTime->format('Y-m-d H:i:s');
            } catch (InvalidFormatException $exception) {
            }
        }
        $parent = Page::getByID($command->getParentID());
        $type = Type::getByID($command->getTypeID());
        $template = Template::getByID($command->getTemplateID());
        $page = $parent->add($type, $data, $template);

        $link = $command->getLink();
        if ($link) {
            /** @var FileService $fileHelper */
            $fileHelper = Application::getFacadeApplication()->make('helper/file');
            if ($fileHelper->getExtension($link) === 'pdf') {
                $this->setDocumentRoot($command->getDocumentRoot());
                $this->setFolderNodeID($command->getFolderID());
                try {
                    $fv = $this->importFile($link);
                    $page->setAttribute($command->getFileHandle(), $fv->getFile());
                } catch (ImportException $exception) {
                    $logger->warning(sprintf('Failed to import file %s (reason: %s)', $link, $exception->getMessage()));
                }
            } else {
                $page->setAttribute($command->getExternalUrlHandle(), $link);
            }
        }

        $topic = $command->getTopic();
        if ($topic) {
            $page->setAttribute($command->getTopicHandle(), ['/' . $topic]);
        }
    }
}
