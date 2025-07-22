<?php
defined('C5_EXECUTE') or die('Access Denied.');

$cookie_value = $cookie_value ?? '';

/** @var \Concrete\Core\View\View $view */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\Form\Service\Form $form */
?>
<form action="<?= $view->action('submit') ?>" method="post">
    <?php $token->output('update_config'); ?>

    <div class="form-group">
        <?= $form->label('cookie_value', t('Cookie Value')) ?>
        <?= $form->textarea('cookie_value', $cookie_value, ['placeholder' => 'foo=bar; baz=qux', 'rows' => 10, 'class' => 'form-control']) ?>
        <p class="help-block"><?= t('Sends this cookie value when the importer retrieves content from the source site. This is useful for sites that require authentication or have specific cookie-based access controls.') ?></p>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button type="submit" class="btn btn-primary float-end">
                <?php echo t('Save') ?>
            </button>
        </div>
    </div>
</form>
