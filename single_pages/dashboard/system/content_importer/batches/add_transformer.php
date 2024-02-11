<?php

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url as UrlFacade;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;
use Macareux\ContentImporter\Entity\BatchItem;
use Macareux\ContentImporter\Transformer\TransformerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Form $form */
/** @var BatchItem $batchItem */
/** @var TransformerInterface[] $transformers */
?>
<form method="get" action="<?= $view->action('add_transformer', $batchItem->getId()) ?>">
    <div class="form-group">
        <?php
        foreach ($transformers as $transformer) {
            ?>
            <div class="form-check mb-2">
                <?= $form->radio('transformer', $transformer->getTransformerHandle(), false, ['id' => $transformer->getTransformerHandle(), 'class' => 'form-check-input', 'required' => 'required']) ?>
                <?= $form->label($transformer->getTransformerHandle(), $transformer->getTransformerName(), ['class' => 'form-check-label', 'aria-describedby' => $transformer->getTransformerHandle() . '-description']) ?>
                <div id="<?= $transformer->getTransformerHandle() ?>-description" class="form-text"><?= $transformer->getTransformerDescription() ?></div>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches/edit_batch', $batchItem->getBatch()->getId()) ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
            <?= $form->submit('save', t('Select Transformer'), ['class' => 'btn btn-primary float-end']) ?>
        </div>
    </div>
</form>
