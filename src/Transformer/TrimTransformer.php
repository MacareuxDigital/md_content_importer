<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\Service\Text;

class TrimTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return t('Trim');
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

    public function renderForm(): void
    {
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function updateFromRequest(Request $request): void
    {
    }
}
