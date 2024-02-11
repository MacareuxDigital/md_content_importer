<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\File\Import\ImportException;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Traits\ImageFileTransformerTrait;
use Symfony\Component\DomCrawler\Crawler;

class ImageFileContentTransformer implements TransformerInterface
{
    use ImageFileTransformerTrait;

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Import Images & Files in HTML');
    }

    public function getTransformerDescription(): string
    {
        return t('Upload images and files to the file manager in HTML content.');
    }

    public function getTransformerHandle(): string
    {
        return 'image_file_content';
    }

    public function renderForm(BatchItem $batchItem): void
    {
        $app = Application::getFacadeApplication();

        $folder = null;
        if ($this->getFolderNodeID()) {
            $folder = FileFolder::getByID($this->getFolderNodeID());
        }
        $documentRoot = $this->getDocumentRoot() ?: $batchItem->getBatch()->getDocumentRoot();
        /** @var \Concrete\Core\Config\Repository\Repository $config */
        $config = $app->make('config');
        /** @var \Concrete\Core\File\Service\Application $helper_file */
        $helper_file = $app->make('helper/concrete/file');
        $extensions = $this->getExtensions() ?: implode(', ', $helper_file->unserializeUploadFileExtensions($config->get('concrete.upload.extensions')));
        $allowedHost = $this->getAllowedHost();
        if (!$allowedHost) {
            $allowedHost = parse_url($documentRoot, PHP_URL_HOST);
        }
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/file', [
            'form' => $app->make('helper/form'),
            'folders' => $this->getFolders(),
            'folder' => $folder,
            'documentRoot' => $documentRoot,
            'extensions' => $extensions,
            'allowedHost' => $allowedHost,
        ], 'md_content_importer')->render();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFolderNodeID($request->get('folderNodeID'));
        $this->setExtensions($request->get('extensions'));
        $this->setDocumentRoot($request->get('documentRoot'));
        $this->setAllowedHost($request->get('allowedHost'));
    }

    public function transform(string $input): string
    {
        $app = Application::getFacadeApplication();
        /** @var ResolverManagerInterface $resolver */
        $resolver = $app->make(ResolverManagerInterface::class);

        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $app->make(LoggerFactory::class);
        $logger = $loggerFactory->createLogger('importer');

        if (strpos($input, '{CCM:') !== false || strpos($input, '<concrete-picture')) {
            $input = LinkAbstractor::translateFrom($input);
        }

        $crawler = new Crawler($input);

        $crawler->filter('img')->each(function (Crawler $node, $i) use ($resolver, $logger) {
            $src = urldecode($node->attr('src'));
            if ($this->validateFile($src)) {
                try {
                    $fv = $this->importFile($src);
                    $domNode = $node->getNode(0);
                    $domNode->setAttribute('src', $resolver->resolve(['/download_file', 'view_inline', $fv->getFileUUID()]));
                } catch (ImportException $exception) {
                    $logger->warning(sprintf('Failed to import file %s (reason: %s)', $src, $exception->getMessage()));
                } catch (\RuntimeException $exception) {
                    $logger->alert(sprintf('Failed to import file %s (reason: %s)', $src, $exception->getMessage()));
                }
            }
        });

        $extensions = $this->getExtensionsArray();
        $crawler->filter('a')->each(function (Crawler $node, $i) use ($resolver, $logger, $extensions) {
            $href = (string) $node->attr('href');
            if ($this->validateFile($href, $extensions)) {
                try {
                    $fv = $this->importFile($href);
                    $domNode = $node->getNode(0);
                    $domNode->setAttribute('href', $resolver->resolve(['/download_file', 'view', $fv->getFileUUID()]));
                } catch (ImportException $exception) {
                    $logger->warning(sprintf('Failed to import file %s (reason: %s)', $href, $exception->getMessage()));
                } catch (\RuntimeException $exception) {
                    $logger->alert(sprintf('Failed to import file %s (reason: %s)', $href, $exception->getMessage()));
                }
            }
        });

        return LinkAbstractor::translateTo($crawler->filter('body')->html());
    }
}
