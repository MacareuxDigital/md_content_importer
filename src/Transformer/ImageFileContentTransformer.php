<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\File\Service\File;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\Url\UrlImmutable;
use Macareux\ContentImporter\Traits\ImageFileTransformerTrait;
use Symfony\Component\DomCrawler\Crawler;

class ImageFileContentTransformer implements TransformerInterface
{
    use ImageFileTransformerTrait;

    private $extensions;

    /**
     * @return mixed
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param mixed $extensions
     */
    public function setExtensions($extensions): void
    {
        $this->extensions = $extensions;
    }

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Import Images & Files in HTML');
    }

    public function getTransformerHandle(): string
    {
        return 'image_file_content';
    }

    public function renderForm(): void
    {
        $app = Application::getFacadeApplication();

        $folder = null;
        if ($this->getFolderNodeID()) {
            $folder = FileFolder::getByID($this->getFolderNodeID());
        }
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/content', [
            'form' => $app->make('helper/form'),
            'folders' => $this->getFolders(),
            'folder' => $folder,
            'documentRoot' => $this->getDocumentRoot(),
            'extensions' => $this->getExtensions(),
        ], 'md_content_importer')->render();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFolderNodeID($request->get('folderNodeID'));
        $this->setExtensions($request->get('extensions'));
        $this->setDocumentRoot($request->get('documentRoot'));
    }

    public function transform(string $input): string
    {
        $app = Application::getFacadeApplication();
        /** @var ResolverManagerInterface $resolver */
        $resolver = $app->make(ResolverManagerInterface::class);
        /* @var \Concrete\Core\Entity\Site\Site $site */
        $site = $app->make('site')->getSite();
        $siteUrl = $site->getSiteCanonicalURL();
        $canonical = UrlImmutable::createFromUrl($siteUrl);
        $crawler = new Crawler($input);

        $crawler->filter('img')->each(function (Crawler $node, $i) use ($resolver, $canonical) {
            $src = $node->attr('src');
            if ($src && strpos($src, $canonical->getHost()) === false) {
                $fv = $this->importFile($src);
                $domNode = $node->getNode(0);
                $domNode->setAttribute('src', $resolver->resolve(['/download_file', 'view_inline', $fv->getFileUUID()]));
            }
        });

        /** @var File $fileHelper */
        $fileHelper = $app->make('helper/file');
        $extensions = $this->getExtensions();
        if ($extensions) {
            $extensions = array_map('trim', explode(',', $extensions));
            $crawler->filter('a')->each(function (Crawler $node, $i) use ($resolver, $fileHelper, $extensions, $canonical) {
                $href = $node->attr('href');
                if ($href && strpos($href, $canonical->getHost()) === false) {
                    $ext = '.' . $fileHelper->getExtension($href);
                    if (in_array($ext, $extensions, true)) {
                        $fv = $this->importFile($href);
                        $domNode = $node->getNode(0);
                        $domNode->setAttribute('href', $resolver->resolve(['/download_file', 'view', $fv->getFileUUID()]));
                    }
                }
            });
        }

        return LinkAbstractor::translateTo($crawler->filter('body')->html());
    }
}
