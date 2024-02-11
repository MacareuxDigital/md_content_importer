<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Macareux\ContentImporter\Entity\BatchItem;

class TrimTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Trim');
    }

    public function getTransformerDescription(): string
    {
        return t('Trim the input string.');
    }

    public function getTransformerHandle(): string
    {
        return 'trim';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        return trim($input);
    }

    public function renderForm(BatchItem $batchItem): void
    {
        // No options
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function updateFromRequest(Request $request): void
    {
        // No options
    }
}
