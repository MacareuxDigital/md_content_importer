<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;

interface TransformerInterface
{
    public function getTransformerName(): string;

    public function getTransformerHandle(): string;

    public function transform(string $input): string;

    public function renderForm(): void;

    public function validateRequest(Request $request): ErrorList;

    public function updateFromRequest(Request $request): void;
}