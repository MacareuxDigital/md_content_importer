<?php
defined('C5_EXECUTE') or die("Access Denied.");

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var string $pattern */
/** @var string $replacement */
?>
<div class="form-group">
    <?= $form->label('pattern', t('The pattern to search for.')) ?>
    <?= $form->text('pattern', $pattern, ['placeholder' => '/(<h1.*>)(.*)(<\/h1>)/uis']) ?>
</div>
<div class="form-group">
    <?= $form->label('replacement', t('The string to replace.')) ?>
    <?= $form->text('replacement', $replacement, ['placeholder' => '<h2>$2</h2>']) ?>
</div>
