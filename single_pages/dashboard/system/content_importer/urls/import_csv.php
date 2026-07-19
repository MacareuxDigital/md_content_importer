<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
?>
<form method="post" action="<?= $view->action('upload_csv') ?>" enctype="multipart/form-data">
    <?= $token->output('upload_csv') ?>
    <div class="form-group">
        <?= $form->label('csv_file', t('CSV File')) ?>
        <?= $form->file('csv_file', ['accept' => '.csv,text/csv']) ?>
        <div class="form-text">
            <?= t('Upload a CSV file of existing URLs (for example, a Screaming Frog export).') ?>
        </div>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= $view->action('view') ?>" class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <button type="submit" class="btn btn-primary float-end"><?= t('Continue') ?></button>
        </div>
    </div>
</form>
