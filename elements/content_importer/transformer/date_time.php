<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var string|null $format */
?>
<div class="form-group">
    <?= $form->label('format', t('Date Time Format')) ?>
    <?= $form->text('format', $format, ['placeholder' => 'Y/m/d']) ?>
</div>
