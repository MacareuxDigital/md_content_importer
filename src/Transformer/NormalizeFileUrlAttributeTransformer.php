<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Traits\NormalizeFileUrlTrait;

/**
 * Transformer to normalize a single file/image URL string.
 * Returns an absolute URL for text/URL attributes — not an image_file fid:N value.
 */
class NormalizeFileUrlAttributeTransformer implements TransformerInterface
{
    use NormalizeFileUrlTrait;

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Normalize File or Image URL');
    }

    public function getTransformerDescription(): string
    {
        return t('Convert a relative file or image URL to an absolute URL under the document root. Returns a URL string for text/URL attributes, not a file ID for image file attributes.');
    }

    public function getTransformerHandle(): string
    {
        return 'normalize_file_url_attribute';
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
        return $this->normalizeFileUrl($input);
    }
}
