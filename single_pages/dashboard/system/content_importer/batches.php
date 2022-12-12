<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\View\View $view */
/** @var \Macareux\ContentImporter\Entity\Batch[] $batches */
?>
<table class="table table-striped">
    <tbody>
    <?php foreach ($batches as $batch) { ?>
        <tr>
            <th><?= h($batch->getName()) ?></th>
            <td>
                <a class="btn btn-primary btn-sm" href="<?= $view->action('edit_batch', $batch->getId()) ?>"><?= t('Edit') ?></a>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
