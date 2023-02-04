<?php

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Search\Pagination\Pagination $pagination */
if (isset($pagination)) {
    ?>
    <div id="ccm-search-results-table">
        <table class="ccm-search-results-table">
            <thead>
            <tr>
                <th><?= t('Original') ?></th>
                <th><?= t('File ID') ?></th>
                <th><?= t('File Name') ?></th>
                <th><?= t('Import Date') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            /** @var \Macareux\ContentImporter\Entity\ImportFileLog $log */
            foreach ($pagination->getCurrentPageResults() as $log) {
                $file = $log->getImportedFile();
                if ($file) {
                    $fID = $file->getFileID();
                    $fName = $file->getApprovedVersion()->getFileName();
                } else {
                    $fID = $fName = t('Deleted');
                }
                ?>
                <tr>
                    <td><?= h($log->getOriginal()) ?></td>
                    <td><?= h($fID) ?></td>
                    <td><?= h($fName) ?></td>
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
