<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Traits\NormalizeFileUrlTrait;
use Symfony\Component\DomCrawler\Crawler;

class NormalizeFileUrlContentTransformer implements TransformerInterface
{
    use NormalizeFileUrlTrait;

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Normalize Linked URLs in HTML');
    }

    public function getTransformerDescription(): string
    {
        return t('Convert relative image and link URLs in HTML content to absolute URLs under the document root, without uploading files.');
    }

    public function getTransformerHandle(): string
    {
        return 'normalize_file_url_content';
    }

    public function renderForm(BatchItem $batchItem): void
    {
        $app = Application::getFacadeApplication();
        $documentRoot = $this->getDocumentRoot() ?: $batchItem->getBatch()->getDocumentRoot();
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/normalize_file_url', [
            'form' => $app->make('helper/form'),
            'documentRoot' => $documentRoot,
        ], 'md_content_importer')->render();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setDocumentRoot(trim((string) $request->get('documentRoot')));
    }

    public function transform(string $input): string
    {
        if (strpos($input, '{CCM:') !== false || strpos($input, '<concrete-picture') !== false) {
            $input = LinkAbstractor::translateFrom($input);
        }

        $crawler = new Crawler($input);

        $crawler->filter('img')->each(function (Crawler $node) {
            $src = $node->attr('src');
            if ($src === null || $src === '') {
                return;
            }
            $normalized = $this->normalizeFileUrl($src);
            if ($normalized !== '') {
                $node->getNode(0)->setAttribute('src', $normalized);
            }
        });

        $crawler->filter('a')->each(function (Crawler $node) {
            $href = $node->attr('href');
            if ($href === null || $href === '') {
                return;
            }
            $normalized = $this->normalizeFileUrl($href);
            if ($normalized !== '') {
                $node->getNode(0)->setAttribute('href', $normalized);
            }
        });

        return LinkAbstractor::translateTo($crawler->filter('body')->html());
    }
}
