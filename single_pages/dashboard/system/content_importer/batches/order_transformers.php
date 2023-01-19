<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Entity\BatchItemTransformer;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var BatchItem $batchItem */
/** @var BatchItemTransformer[] $transformers */
?>
<form method="post" action="<?= $view->action('submit_transformer_order', $batchItem->getId()) ?>">
    <?php $token->output('order_transformers') ?>
    <div class="form-group">
        <div id="transformers" class="list-group mb-4">
            <?php foreach ($transformers as $transformer) { ?>
                <div class="list-group-item" style="cursor: move">
                    <?= $form->hidden('transformerOrder[]', $transformer->getId()) ?>
                    <?= h($transformer->getClass()->getTransformerName()) ?>
                    <i class="ccm-item-select-list-sort ui-sortable-handle"></i>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches/edit_batch', $batchItem->getBatch()->getId()) ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', t('Change Order'), ['class' => 'btn btn-primary float-end']) ?>
        </div>
    </div>
</form>
<script>
    $(function () {
        $("div#transformers").sortable({
            cursor: 'move',
            opacity: 0.5
        });
    });
</script>
