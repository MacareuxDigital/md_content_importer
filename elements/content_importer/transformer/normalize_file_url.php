<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var string|null $documentRoot */
$documentRoot = $documentRoot ?? null;
?>
<div class="form-group">
    <?= $form->label('documentRoot', t('Document Root')) ?>
    <?= $form->text('documentRoot', $documentRoot, [
        'placeholder' => 'https://www.example.com/path/',
        'aria-describedby' => 'normalizeDocumentRootHelp',
    ]) ?>
    <div id="normalizeDocumentRootHelp" class="form-text">
        <?= t('Relative URLs are resolved against this document root into absolute URLs. Leading %s segments go up one level from the document root, like a browser resolves links.', '<code>../</code>') ?>
    </div>
</div>
