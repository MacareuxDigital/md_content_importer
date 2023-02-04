<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var string $search */
/** @var string $replace */
?>
<div class="form-group">
    <?= $form->label('search', t('The search value')) ?>
    <?= $form->text('search', $search) ?>
</div>
<div class="form-group">
    <?= $form->label('replace', t('The replacement value that replaces found search values')) ?>
    <?= $form->text('replace', $replace) ?>
</div>
