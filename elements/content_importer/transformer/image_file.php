<?php
defined('C5_EXECUTE') or die("Access Denied.");

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var string $search */
/** @var string $replace */
/** @var \Concrete\Core\Tree\Node\Type\FileFolder $folder */
$folderNodeID = $folder ? $folder->getTreeNodeID() : null;
$folders = $folders ?? [];
?>
<div class="form-group">
    <?= $form->label('folderNodeID', t('Parent Folder')) ?>
    <?= $form->select('folderNodeID', $folders, $folderNodeID) ?>
</div>
