<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Search\Pagination\Pagination $pagination */
if (isset($pagination)) {
    ?>
    <div id="ccm-search-results-table">
        <table class="ccm-search-results-table">
            <thead>
            <tr>
                <th><?= t('Batch') ?></th>
                <th><?= t('Original') ?></th>
                <th><?= t('Imported') ?></th>
                <th><?= t('Import Date') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            /** @var \Macareux\ContentImporter\Entity\ImportBatchLog $log */
            foreach ($pagination->getCurrentPageResults() as $log) {
                $batch = $log->getBatch();
                if ($batch) {
                    $batchName = $batch->getName();
                } else {
                    $batchName = t('Deleted');
                }
                $page = $log->getImportedPage();
                if ($page) {
                    $imported = sprintf('<a href="%s" target="_blank">%s</a>', h($page->getCollectionLink()), h($page->getCollectionPath()));
                } else {
                    $imported = t('Deleted');
                }
                ?>
                <tr>
                    <td><?= h($batchName) ?></td>
                    <td><?= h($log->getOriginal()) ?></td>
                    <td><?= $imported ?></td>
                    <td><?= h($log->getImportDate()->format('Y-m-d H:i:s')) ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
    echo $pagination->renderView('dashboard');
}
