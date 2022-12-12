<?php

use Concrete\Core\Page\Type\Composer\FormLayoutSet;
use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\View\View;
use Macareux\ContentImporter\Entity\Batch;
use Macareux\ContentImporter\Entity\BatchItem;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var View $view */
/** @var Token $token */
/** @var Type $pageType */
/** @var Batch $batch */
/** @var FormLayoutSet[] $formLayoutSets */
/** @var BatchItem[] $batchItems */

foreach ($formLayoutSets as $formLayoutSet) {
    $formLayoutSetName = $formLayoutSet->getPageTypeComposerFormLayoutSetDisplayName();
    $formLayoutSetControls = FormLayoutSetControl::getList($formLayoutSet);
    ?>
    <fieldset>
        <legend><?= $formLayoutSetName ?></legend>
        <?php
        /** @var FormLayoutSetControl $formLayoutSetControl */
        foreach ($formLayoutSetControls as $formLayoutSetControl) {
            $formLayoutSetControlID = $formLayoutSetControl->getPageTypeComposerFormLayoutSetControlID();
            ?>
            <div class="card mb-3">
                <div class="card-header">
                    <?= $formLayoutSetControl->getPageTypeComposerControlDisplayLabel() ?>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($batchItems[$formLayoutSetControlID])) {
                        $batchItem = $batchItems[$formLayoutSetControlID];
                        ?>
                        <h5>
                            <?= h($batchItem->getXpath()) ?>
                            <?= h($batchItem->getSelector()) ?>
                        </h5>
                        <?php
                        $transformers = $batchItem->getBatchItemTransformers();
                        if ($transformers) {
                            ?>
                            <div class="list-group mb-4">
                                <?php foreach ($transformers as $transformer) { ?>
                                    <a class="list-group-item" href="<?= $view->action('edit_transformer', $transformer->getId()) ?>"><?= h($transformer->getClass()->getTransformerName()) ?></a>
                                <?php } ?>
                            </div>
                            <?php
                        }
                        ?>
                        <a href="<?= $view->action('add_transformer', $batchItem->getId()) ?>"
                           class="btn btn-primary btn-sm"><?= t('Add Transformer') ?></a>
                        <?php
                    } else {
                        ?>
                        <a href="<?= $view->action('add_batch_item', $batch->getId(), $formLayoutSetControlID) ?>"
                           class="btn btn-primary btn-sm"><?= t('Set Selector') ?></a>
                    <?php } ?>
                </div>
            </div>
            <?php
        }
        ?>
    </fieldset>
    <?php
}
