<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Form\Service\Widget\PageSelector $pageSelector */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
/** @var \Macareux\ContentImporter\Entity\ImportUrl $importUrl */

$importedPage = $importUrl->getImportedPage();
?>
<form method="post" action="<?= h($view->action('submit_imported_page')) ?>">
    <?= $token->output('submit_imported_page') ?>
    <?= $form->hidden('url_id', $importUrl->getId()) ?>

    <div class="form-group">
        <?= $form->label('original_url', t('Original URL')) ?>
        <div>
            <a href="<?= h($importUrl->getUrl()) ?>" target="_blank" rel="noopener noreferrer">
                <?= h($importUrl->getUrl()) ?>
            </a>
        </div>
    </div>

    <div class="form-group">
        <?= $form->label('imported_cID', t('Imported Page')) ?>
        <?= $pageSelector->selectPage('imported_cID', $importedPage ? $importedPage->getCollectionID() : null) ?>
        <div class="form-text">
            <?= t('Setting a page manually marks this URL as imported and removes it from its assigned batch.') ?>
        </div>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= h($view->action('view')) ?>" class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <button type="submit" class="btn btn-primary float-end"><?= t('Save') ?></button>
        </div>
    </div>
</form>
