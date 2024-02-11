<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\Service\Text;
use Macareux\ContentImporter\Entity\BatchItem;

class UrlifyTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return t('URLify');
    }

    public function getTransformerDescription(): string
    {
        return t('Convert text to "lowercase-dash-separated" format.');
    }

    public function getTransformerHandle(): string
    {
        return 'urlify';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        /** @var Text $text */
        $text = Application::getFacadeApplication()->make(Text::class);

        return $text->urlify($input);
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
