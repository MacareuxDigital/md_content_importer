<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Macareux\ContentImporter\Entity\BatchItem;

interface TransformerInterface
{
    public function getTransformerName(): string;

    public function getTransformerDescription(): string;

    public function getTransformerHandle(): string;

    public function supportPreview(): bool;

    public function transform(string $input): string;

    public function renderForm(BatchItem $batchItem): void;

    public function validateRequest(Request $request): ErrorList;

    public function updateFromRequest(Request $request): void;
}
