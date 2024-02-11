<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\PageSelector;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var PageSelector $pageSelector */
$batchID = $batchID ?? null;
$name = $name ?? null;
$documentRoot = $documentRoot ?? null;
$sourcePath = $sourcePath ?? null;
$pageTypeID = $pageTypeID ?? null;
$pageTypeIDs = $pageTypeIDs ?? [];
$pageTemplateID = $pageTemplateID ?? null;
$pageTemplateIDs = $pageTemplateIDs ?? [];
$parentCID = $parentCID ?? null;
$submitLabel = $batchID ? t('Save Batch') : t('Add Batch');
?>
<form method="post" action="<?= $view->action('submit_batch') ?>">
    <?php $token->output('submit_batch') ?>
    <?= $form->hidden('batchID', $batchID) ?>
    <div class="form-group">
        <?= $form->label('name', t('Batch Name')) ?>
        <?= $form->text('name', $name) ?>
    </div>
    <div class="form-group">
        <?= $form->label('sourcePath', t('Source Paths')) ?>
        <?= $form->textarea('sourcePath', $sourcePath, ['rows' => 5, 'aria-describedby' => 'sourcePathHelp']) ?>
        <div id="sourcePathHelp" class="form-text">
            <?= t('Enter one source path per line. Paths can be absolute or full URLs.') ?>
        </div>
    </div>
    <div class="form-group">
        <?= $form->label('documentRoot', t('Document Root')) ?>
        <?= $form->text('documentRoot', $documentRoot, ['aria-describedby' => 'documentRootHelp']) ?>
        <div id="documentRootHelp" class="form-text">
            <?= t('Enter the document root of the source paths. This is used to generate page paths.') ?>
        </div>
    </div>
    <div class="form-group">
        <?= $form->label('pageTypeID', t('Target Page Type')) ?>
        <?= $form->select('pageTypeID', $pageTypeIDs, $pageTypeID, ['aria-describedby' => 'pageTypeIDHelp']) ?>
        <div id="pageTypeIDHelp" class="form-text">
            <?= t('Select the page type to use for the imported pages.') ?>
        </div>
    </div>
    <div class="form-group">
        <?= $form->label('pageTemplateID', t('Target Page Template')) ?>
        <?= $form->select('pageTemplateID', $pageTemplateIDs, $pageTemplateID, ['aria-describedby' => 'pageTemplateIDHelp']) ?>
        <div id="pageTemplateIDHelp" class="form-text">
            <?= t('Select the page template to use for the imported pages.') ?>
        </div>
    </div>
    <div class="form-group">
        <?= $form->label('parentCID', t('Target Parent Page')) ?>
        <?= $pageSelector->selectPage('parentCID', $parentCID, ['aria-describedby' => 'parentCIDHelp']) ?>
        <div id="parentCIDHelp" class="form-text">
            <?= t('Select the parent page for the imported pages.') ?>
        </div>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches') ?>" class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', $submitLabel, ['class' => 'btn btn-primary float-end']) ?>
        </div>
    </div>
</form>
