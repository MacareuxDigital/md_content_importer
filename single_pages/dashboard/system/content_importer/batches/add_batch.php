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
$pageTypeIDs = $pageTypeIDs ?? [];
$pageTemplateIDs = $pageTemplateIDs ?? [];
?>
<form method="post" action="<?= $view->action('submit_batch') ?>">
    <?php $token->output('submit_batch') ?>
    <div class="form-group">
        <?= $form->label('name', t('Batch Name')) ?>
        <?= $form->text('name') ?>
    </div>
    <div class="form-group">
        <?= $form->label('sourcePath', t('Source Paths')) ?>
        <?= $form->textarea('sourcePath', ['rows' => 5]) ?>
    </div>
    <div class="form-group">
        <?= $form->label('pageTypeID', t('Page Type')) ?>
        <?= $form->select('pageTypeID', $pageTypeIDs) ?>
    </div>
    <div class="form-group">
        <?= $form->label('pageTemplateID', t('Page Template')) ?>
        <?= $form->select('pageTemplateID', $pageTemplateIDs) ?>
    </div>
    <div class="form-group">
        <?= $form->label('parentCID', t('Parent Page')) ?>
        <?= $pageSelector->selectPage('parentCID') ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches') ?>" class="btn btn-secondary float-start"><?=  t('Cancel') ?></a>
            <?= $form->submit('save', t('Add Batch'), ['class' => 'btn btn-primary float-end']) ?>
        </div>
    </div>
</form>
