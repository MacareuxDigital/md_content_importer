<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\File\Import\ImportException;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;
use Concrete\Core\Url\UrlImmutable;
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
        ], 'md_content_importer')->render();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFolderNodeID($request->get('folderNodeID'));
        $this->setDocumentRoot($request->get('documentRoot'));
    }

    public function transform(string $input): string
    {
        $app = Application::getFacadeApplication();
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $app->make(LoggerFactory::class);
        $logger = $loggerFactory->createLogger('importer');
        /* @var \Concrete\Core\Entity\Site\Site $site */
        $site = $app->make('site')->getSite();
        $siteUrl = $site->getSiteCanonicalURL();
        if ($siteUrl) {
            $canonical = UrlImmutable::createFromUrl($siteUrl);
        } else {
            $canonical = UrlImmutable::createFromUrl(Request::getInstance()->getUri());
        }
        if ($input && strpos($input, (string) $canonical->getHost()) === false) {
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
