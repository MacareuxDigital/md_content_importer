<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Tree\Node\Type\FileFolder $folder */
$folderNodeID = $folder ? $folder->getTreeNodeID() : null;
$folders = $folders ?? [];
$documentRoot = $documentRoot ?? null;
$allowedHost = $allowedHost ?? null;
?>
<div class="form-group">
    <?= $form->label('folderNodeID', t('Parent Folder')) ?>
    <?= $form->select('folderNodeID', $folders, $folderNodeID) ?>
</div>
<div class="form-group">
    <?= $form->label('documentRoot', t('Document Root')) ?>
    <?= $form->text('documentRoot', $documentRoot, ['placeholder' => '/path/to/directory or https://www.example.com']) ?>
</div>
<div class="form-group">
    <?= $form->label('allowedHost', t('Allowed host to download files')) ?>
    <?= $form->text('allowedHost', $allowedHost, ['placeholder' => 'www.example.com']) ?>
</div>
