<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\Service\Text;

class UrlifyTransformer implements TransformerInterface
{
    public function getTransformerName(): string
    {
        return t('URLify');
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
