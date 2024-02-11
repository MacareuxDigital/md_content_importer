<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Macareux\ContentImporter\Entity\BatchItem;

interface TransformerInterface
{
    /**
     * Get the name of the transformer
     *
     * @return string
     */
    public function getTransformerName(): string;

    /**
     * Get the description of the transformer
     *
     * @return string
     */
    public function getTransformerDescription(): string;

    /**
     * Get the handle of the transformer
     *
     * @return string
     */
    public function getTransformerHandle(): string;

    /**
     * Whether the transformer support preview
     *
     * @return bool
     */
    public function supportPreview(): bool;

    /**
     * Transform the input and return the result
     *
     * @param string $input
     * @return string
     */
    public function transform(string $input): string;

    /**
     * Render the form for add or edit a transformer
     *
     * @param \Macareux\ContentImporter\Entity\BatchItem $batchItem
     * @return void
     */
    public function renderForm(BatchItem $batchItem): void;

    /**
     * Validate the form request on add or edit a transformer
     *
     * @param \Concrete\Core\Http\Request $request
     * @return \Concrete\Core\Error\ErrorList\ErrorList
     */
    public function validateRequest(Request $request): ErrorList;

    /**
     * Update the transformer from the form request
     *
     * @param \Concrete\Core\Http\Request $request
     * @return void
     */
    public function updateFromRequest(Request $request): void;
}
