<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
/** @var \Macareux\ContentImporter\Entity\Batch[] $batches */
?>
<table class="table table-striped">
    <tbody>
    <?php foreach ($batches as $batch) { ?>
        <tr>
            <th><?= h($batch->getName()) ?></th>
            <td style="text-align: right">
                <a class="btn btn-secondary btn-sm"
                   href="<?= $view->action('edit_batch_basic', $batch->getId()) ?>"><?= t('Source & Publish Target') ?></a>
                <a class="btn btn-secondary btn-sm"
                   href="<?= $view->action('edit_batch', $batch->getId()) ?>"><?= t('Selectors & Transformers') ?></a>
                <a class="btn btn-secondary btn-sm"
                   href="<?= $view->action('copy_batch', $batch->getId()) ?>"><?= t('Copy') ?></a>
                <button class="btn btn-primary btn-sm" type="button" data-batch-id="<?= $batch->getId() ?>"
                        data-dialog="import-batch"><?= t('Import') ?></button>
                <button class="btn btn-danger btn-sm" type="button" data-batch-id="<?= $batch->getId() ?>"
                        data-dialog="delete-batch"><?= t('Delete') ?></button>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<div style="display: none">
    <div id="ccm-dialog-import-batch" class="ccm-ui">
        <form method="post" class="form-stacked" action="<?= $view->action('import_batch'); ?>">
            <?= $token->output('import_batch') ?>
            <?= $form->hidden('batch_id') ?>
            <p><?= t('Are you sure? This action cannot be undone.') ?></p>
            <div class="form-check">
                <label>
                    <?= $form->checkbox('skip_imported', 1) ?>
                    <?= t('Skip already imported page.') ?>
                </label>
            </div>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-secondary float-start"
                    onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-primary float-end"
                    onclick="$('#ccm-dialog-import-batch form').submit()"><?= t('Import') ?></button>
        </div>
    </div>
    <div id="ccm-dialog-delete-batch" class="ccm-ui">
        <form method="post" class="form-stacked" action="<?= $view->action('delete_batch'); ?>">
            <?= $token->output('delete_batch') ?>
            <?= $form->hidden('delete_batch_id') ?>
            <p><?= t('Are you sure? This action cannot be undone.') ?></p>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-secondary float-start"
                    onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-danger float-end"
                    onclick="$('#ccm-dialog-delete-batch form').submit()"><?= t('Delete') ?></button>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $('button[data-dialog=import-batch]').on('click', function () {
            $('#batch_id').val($(this).data('batch-id'));
            jQuery.fn.dialog.open({
                element: '#ccm-dialog-import-batch',
                modal: true,
                width: 320,
                title: '<?=t("import Batch") ?>',
                height: 'auto'
            });
        });
        $('#ccm-dialog-import-batch form').on('submit', function () {
            new ConcreteProgressiveOperation({
                url: $(this).attr('action'),
                data: $(this).serializeArray(),
                title: <?= json_encode(t('Import Pages')) ?>,
                onComplete: function () {
                    window.location.href = <?=json_encode((string)$this->action('import_completed')) ?>;
                }
            });
            return false;
        });
        $('button[data-dialog=delete-batch]').on('click', function () {
            $('#delete_batch_id').val($(this).data('batch-id'));
            jQuery.fn.dialog.open({
                element: '#ccm-dialog-delete-batch',
                modal: true,
                width: 320,
                title: '<?=t("Delete Batch") ?>',
                height: 'auto'
            });
        });
    });
</script>
