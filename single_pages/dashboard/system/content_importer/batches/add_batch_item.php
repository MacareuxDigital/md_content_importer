<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var \Macareux\ContentImporter\Entity\Batch $batch */
/** @var \Concrete\Core\Page\Type\Composer\FormLayoutSetControl $formLayoutSetControl */
?>
<form method="post" action="<?= $view->action('submit_batch_item') ?>" id="batch-item-form">
    <?php $token->output('submit_batch_item') ?>
    <?= $form->hidden('batch', $batch->getId()) ?>
    <?= $form->hidden('formLayoutSetControl', $formLayoutSetControl->getPageTypeComposerFormLayoutSetControlID()) ?>
    <div class="form-group">
        <?= $form->label('filter', t('Filter')) ?>
        <div class="form-check">
            <?= $form->radio('filterType', 'xpath') ?>
            <?= $form->label('filterType1', t('Xpath')) ?>
        </div>
        <div class="form-check">
            <?= $form->radio('filterType', 'selector') ?>
            <?= $form->label('filterType2', t('CSS Selector')) ?>
        </div>
        <?= $form->text('filter') ?>
    </div>
    <div class="form-group">
        <div id="preview-alert" class="alert alert-warning" role="alert" style="display: none"></div>
        <?= $form->label('preview-result', t('Preview Result')) ?>
        <?= $form->textarea('preview-result', ['rows' => 10, 'readonly' => true]) ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches/edit_batch', $batch->getId()) ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', t('Add Batch Item'), ['class' => 'btn btn-primary float-end ms-2']) ?>
            <?= $form->button('preview', t('Preview'), ['class' => 'btn btn-secondary float-end']) ?>
        </div>
    </div>
</form>
<script>
    $(function () {
        $('#preview').on('click', function () {
            $.ajax({
                url: "<?= $view->action('preview_batch_item') ?>",
                data: $('#batch-item-form').serialize(),
                dataType: "json"
            }).done(function (response) {
                if (response.error) {
                    $('#preview-alert').show().text(response.errors.join());
                } else {
                    $('#preview-alert').hide();
                    $('#preview-result').text(response.response);
                }
            })
        })
    })
</script>
