<?php

use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\DateTimeCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\PageTemplateCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\PublishTargetCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\UserCorePageProperty;
use Concrete\Core\Page\Type\Composer\Control\CorePageProperty\VersionCommentCorePageProperty;
use Concrete\Core\Page\Type\Composer\FormLayoutSet;
use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\Support\Facade\Url as UrlFacade;
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
            $composerControlObject = $formLayoutSetControl->getPageTypeComposerControlObject();
            if ($composerControlObject instanceof PublishTargetCorePageProperty) {
                continue;
            }
            if ($composerControlObject instanceof UserCorePageProperty) {
                continue;
            }
            if ($composerControlObject instanceof VersionCommentCorePageProperty) {
                continue;
            }
            if ($composerControlObject instanceof PageTemplateCorePageProperty) {
                continue;
            }
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
                        $selector = $batchItem->getSelector();
                        $contentType = t('HTML');
                        if ($batchItem->getContentType() === BatchItem::CONTENT_TEXT) {
                            $contentType = t('Text');
                        }
                        if ($batchItem->getContentType() === BatchItem::CONTENT_ATTRIBUTE) {
                            $contentType = t('%s attribute', $batchItem->getAttribute());
                        }
                        ?>
                        <div class="alert alert-info">
                            <?php
                            if ($batchItem->getFilterType() === BatchItem::TYPE_XPATH) {
                                echo t('Get %s from XPath: %s', $contentType, $selector);
                            }
                            if ($batchItem->getFilterType() === BatchItem::TYPE_SELECTOR) {
                                echo t('Get %s from selector: %s', $contentType, $selector);
                            }
                            if ($batchItem->getFilterType() === BatchItem::TYPE_FILENAME) {
                                echo t('Get filename');
                            }
                            if ($batchItem->getFilterType() === BatchItem::TYPE_FILEPATH) {
                                echo t('Get filepath');
                            }
                            ?>
                            <div class="btn-group float-end" role="group" aria-label="<?= t('Selector Actions') ?>">
                                <a href="<?= $view->action('edit_batch_item', $batchItem->getId()) ?>"
                                   class="btn btn-light btn-sm"><?= t('Edit Selector') ?></a>
                            </div>
                        </div>
                        <?php
                        $transformers = $batchItem->getBatchItemTransformers();
                        if ($transformers->count() > 0) {
                            ?>
                            <h6><?= t('Transformers') ?></h6>
                            <div class="list-group mb-4">
                                <?php foreach ($transformers as $transformer) { ?>
                                    <a class="list-group-item"
                                       href="<?= $view->action('edit_transformer', $transformer->getId()) ?>"><?= h($transformer->getClass()->getTransformerName()) ?></a>
                                <?php } ?>
                            </div>
                            <?php
                        } elseif ($composerControlObject instanceof DateTimeCorePageProperty) {
                            ?>
                            <div class="alert alert-warning"><?= t('You must set Date Time transformer.') ?></div>
                            <?php
                        }
                        ?>
                        <a href="<?= $view->action('add_transformer', $batchItem->getId()) ?>"
                           class="btn btn-primary btn-sm"><?= t('Add Transformer') ?></a>
                        <?php
                        if ($transformers->count() > 1) { ?>
                            <a href="<?= $view->action('order_transformers', $batchItem->getId()) ?>"
                               class="btn btn-secondary btn-sm"><?= t('Order Transformer') ?></a>
                            <?php
                        }
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

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches') ?>"
               class="btn btn-secondary float-start"><?= t('Cancel') ?></a>
        </div>
    </div>
    <?php
}
