<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\File\Import\ImportException;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;
use Macareux\ContentImporter\Traits\ImageFileTransformerTrait;

/**
 * Transformer to generate text representation for image_file attribute. e.g. "fid:1".
 */
class ImageFileAttributeTransformer implements TransformerInterface
{
    use ImageFileTransformerTrait;

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Import Image or File for Image File Attribute');
    }

    public function getTransformerHandle(): string
    {
        return 'image_file_attribute';
    }

    public function renderForm(): void
    {
        $app = Application::getFacadeApplication();

        $folder = null;
        if ($this->getFolderNodeID()) {
            $folder = FileFolder::getByID($this->getFolderNodeID());
        }
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/image_file', [
            'form' => $app->make('helper/form'),
            'folders' => $this->getFolders(),
            'folder' => $folder,
            'documentRoot' => $this->getDocumentRoot(),
            'allowedHost' => $this->getAllowedHost(),
        ], 'md_content_importer')->render();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFolderNodeID($request->get('folderNodeID'));
        $this->setDocumentRoot($request->get('documentRoot'));
        $this->setAllowedHost($request->get('allowedHost'));
    }

    public function transform(string $input): string
    {
        $app = Application::getFacadeApplication();
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $app->make(LoggerFactory::class);
        $logger = $loggerFactory->createLogger('importer');

        if ($this->validateFile($input)) {
            try {
                $fv = $this->importFile(urldecode($input));

                return 'fid:' . $fv->getFileID();
            } catch (ImportException $exception) {
                $logger->warning(sprintf('Failed to import file %s (reason: %s)', $input, $exception->getMessage()));
            }
        }

        return '';
    }
}
