<?php

namespace Macareux\ContentImporter\Traits;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;

trait ImageFileTransformerTrait
{
    use FileImporterTrait;

    public function supportPreview(): bool
    {
        return false;
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }
}
