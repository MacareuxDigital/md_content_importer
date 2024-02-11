<?php

use Concrete\Core\Support\Facade\Url as UrlFacade;

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Tree\Node\Type\FileFolder $folder */
$folderNodeID = $folder ? $folder->getTreeNodeID() : null;
$folders = $folders ?? [];
$documentRoot = $documentRoot ?? null;
$extensions = $extensions ?? '';
$allowedHost = $allowedHost ?? null;
?>
<div class="form-group">
    <?= $form->label('folderNodeID', t('Import files to')) ?>
    <?= $form->select('folderNodeID', $folders, $folderNodeID) ?>
</div>
<div class="form-group">
    <?= $form->label('documentRoot', t('Document Root')) ?>
    <?= $form->text('documentRoot', $documentRoot, ['placeholder' => '/path/to/directory or https://www.example.com', 'aria-describedby' => 'documentRootHelp']) ?>
    <div id="documentRootHelp" class="form-text">
        <?= t('If file paths does not start with http:// or https://, the document root will be prepended to the file path.') ?>
    </div>
</div>
<div class="form-group">
    <?= $form->label('extensions', t('File Extensions to allow download')) ?>
    <?= $form->textarea('extensions', $extensions, ['row' => 3, 'placeholder' => 'pdf, xlsx', 'aria-describedby' => 'extensionsHelp']) ?>
    <div id="extensionsHelp" class="form-text">
        <?= t('Comma separated list of file extensions to allow download. If you input an extension that is not listed in %sAllowed File Types%s, it will be ignored.', '<a href="' . UrlFacade::to('/dashboard/system/files/filetypes') . '" target="_blank">', '</a>') ?>
        <br>
        <?= t('In general, you do not want to allow web pages to be downloaded, so you should not include html, htm, php, asp, aspx, etc.') ?>
    </div>
</div>
<div class="form-group">
    <?= $form->label('allowedHost', t('Allowed host to download files')) ?>
    <?= $form->text('allowedHost', $allowedHost, ['placeholder' => 'www.example.com']) ?>
    <div id="allowedHostHelp" class="form-text">
        <?= t('This transformer will only download files from the provided host. If you leave this field empty, every host will be allowed.') ?>
        <br>
        <?= t('In general, you need to download files from the legacy site only, and no need to download external files.') ?>
    </div>
</div>
