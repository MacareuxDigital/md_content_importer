<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;
use Macareux\ContentImporter\Entity\BatchItem;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var BatchItem $batchItem */
/** @var \Concrete\Core\Page\Type\Composer\FormLayoutSetControl $formLayoutSetControl */
$filter = '';
if ($batchItem->getFilterType() !== BatchItem::TYPE_FILENAME) {
    $filter = $batchItem->getSelector();
}
?>
<form method="post" action="<?= $view->action('submit_batch_item') ?>" id="batch-item-form">
    <?php $token->output('submit_batch_item') ?>
    <?= $form->hidden('batch', $batchItem->getBatch()->getId()) ?>
    <?= $form->hidden('batchItem', $batchItem->getId()) ?>
    <?= $form->hidden('formLayoutSetControl', $formLayoutSetControl->getPageTypeComposerFormLayoutSetControlID()) ?>
    <div class="form-group">
        <?= $form->label('filter', t('DOM Filter')) ?>
        <div class="form-check">
            <?= $form->radio('filterType', BatchItem::TYPE_XPATH, $batchItem->getFilterType() === BatchItem::TYPE_XPATH) ?>
            <?= $form->label('filterType1', t('Xpath')) ?>
        </div>
        <div class="form-check">
            <?= $form->radio('filterType', BatchItem::TYPE_SELECTOR, $batchItem->getFilterType() === BatchItem::TYPE_SELECTOR) ?>
            <?= $form->label('filterType2', t('CSS Selector')) ?>
        </div>
        <div class="form-check">
            <?= $form->radio('filterType', BatchItem::TYPE_FILENAME, $batchItem->getFilterType() === BatchItem::TYPE_FILENAME) ?>
            <?= $form->label('filterType3', t('File Name')) ?>
        </div>
        <?php
        $options = [];
        if ($batchItem->getFilterType() === BatchItem::TYPE_FILENAME) {
            $options['style'] = 'display: none';
        }
        echo $form->text('filter', $filter, $options);
        ?>
    </div>
    <div class="form-group" id="content-type-section">
        <?= $form->label('content', t('Content Type')) ?>
        <div class="form-check">
            <?= $form->radio('contentType', BatchItem::CONTENT_HTML, $batchItem->getContentType() === BatchItem::CONTENT_HTML) ?>
            <?= $form->label('contentType4', t('Inner HTML')) ?>
        </div>
        <div class="form-check">
            <?= $form->radio('contentType', BatchItem::CONTENT_TEXT, $batchItem->getContentType() === BatchItem::CONTENT_TEXT) ?>
            <?= $form->label('contentType5', t('Inner Text')) ?>
        </div>
        <div class="form-check">
            <?= $form->radio('contentType', BatchItem::CONTENT_ATTRIBUTE, $batchItem->getContentType() === BatchItem::CONTENT_ATTRIBUTE) ?>
            <?= $form->label('contentType6', t('Attribute')) ?>
        </div>
        <?php
        $options = [];
        if ($batchItem->getContentType() !== BatchItem::CONTENT_ATTRIBUTE) {
            $options['style'] = 'display: none';
        }
        echo $form->text('attribute', $batchItem->getAttribute(), $options);
        ?>
    </div>
    <div class="form-group">
        <div id="preview-alert" class="alert alert-warning" role="alert" style="display: none"></div>
        <?= $form->label('preview-result', t('Preview Result')) ?>
        <?= $form->textarea('preview-result', ['rows' => 10, 'disabled' => true]) ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches/edit_batch', $batchItem->getBatch()->getId()) ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', t('Save Selector'), ['class' => 'btn btn-primary float-end ms-2']) ?>
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
        $('[name=filterType]').on('change', function () {
            if (this.value === '3') {
                $('#filter').hide()
                $('#content-type-section').hide()
            } else {
                $('#filter').show()
                $('#content-type-section').show()
            }
        })
        $('[name=contentType]').on('change', function () {
            if (this.value === '30') {
                $('#attribute').show()
            } else {
                $('#attribute').hide()
            }
        })
    })
</script>
