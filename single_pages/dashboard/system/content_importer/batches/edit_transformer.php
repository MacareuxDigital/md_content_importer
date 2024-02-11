<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Entity\BatchItemTransformer;
use Macareux\ContentImporter\Transformer\TransformerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var BatchItem $batchItem */
/** @var TransformerInterface $transformer */
/** @var BatchItemTransformer|null $batchItemTransformer */
$originalString = $originalString ?? '';
$batchItemTransformer = $batchItemTransformer ?? null;
if ($batchItemTransformer) {
    $cancelLink = UrlFacade::to('/dashboard/system/content_importer/batches/edit_batch', $batchItemTransformer->getBatchItem()->getBatch()->getId());
    ?>
    <div class="ccm-dashboard-header-buttons">
        <button data-dialog="delete-item" class="btn btn-danger"><?= t('Delete') ?></button>
    </div>
    <div style="display: none">
        <div id="ccm-dialog-delete-item" class="ccm-ui">
            <form method="post" class="form-stacked" action="<?= $view->action('delete_transformer') ?>">
                <?= $token->output('delete_transformer') ?>
                <?= $form->hidden('transformer', $batchItemTransformer->getId()) ?>
                <p><?= t('Are you sure? This action cannot be undone.') ?></p>
            </form>
            <div class="dialog-buttons">
                <button class="btn btn-secondary float-start"
                        onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
                <button class="btn btn-danger float-end"
                        onclick="$('#ccm-dialog-delete-item form').submit()"><?= t('Delete') ?></button>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function () {
            $('button[data-dialog=delete-item]').on('click', function () {
                jQuery.fn.dialog.open({
                    element: '#ccm-dialog-delete-item',
                    modal: true,
                    width: 320,
                    title: '<?= t('Delete Transformer') ?>',
                    height: 'auto'
                });
            });
        });
    </script>
    <?php
} else {
    $cancelLink = UrlFacade::to('/dashboard/system/content_importer/batches/add_transformer', $batchItem->getId());
}
?>
<form id="transformer-form" method="post" action="<?= $view->action('submit_transformer', $batchItem->getId()) ?>">
    <?php
    $token->output('submit_transformer');
    if ($batchItemTransformer) {
        echo $form->hidden('transformer', $batchItemTransformer->getId());
        $submitLabel = t('Save Transformer');
    } else {
        echo $form->hidden('transformer', $transformer->getTransformerHandle());
        $submitLabel = t('Add Transformer');
    }
    ?>
    <fieldset>
        <?php $transformer->renderForm($batchItem) ?>
    </fieldset>
    <?php if ($transformer->supportPreview()) { ?>
        <fieldset>
            <legend><?= t('Preview') ?></legend>
            <div class="form-group">
                <div id="preview-alert" class="alert alert-warning" role="alert" style="display: none"></div>
                <div class="mb-3 row">
                    <?= $form->label('original', t('Original'), ['class' => 'col-sm-2']) ?>
                    <div class="col-sm-10">
                        <?= $form->textarea('original', $originalString, ['rows' => 5]) ?>
                    </div>
                </div>
                <div class="mb-3 row">
                    <?= $form->label('preview-result', t('Preview Result'), ['class' => 'col-sm-2']) ?>
                    <div class="col-sm-10">
                        <?= $form->textarea('preview-result', ['rows' => 5, 'disabled' => true]) ?>
                    </div>
                </div>
            </div>
        </fieldset>
    <?php } ?>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= $cancelLink ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', $submitLabel, ['class' => 'btn btn-primary float-end ms-2']) ?>
            <?php if ($transformer->supportPreview()) { ?>
                <?= $form->button('preview', t('Preview'), ['class' => 'btn btn-secondary float-end']) ?>
            <?php } ?>
        </div>
    </div>
</form>
<script>
    $(function () {
        $('#preview').on('click', function () {
            $.ajax({
                url: "<?= $view->action('preview_transformer', $batchItem->getId()) ?>",
                data: $('#transformer-form').serialize(),
                method: "POST",
                dataType: "json"
            }).done(function (response) {
                console.log(response)
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
