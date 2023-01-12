<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
/** @var \Concrete\Core\Form\Service\Widget\PageSelector $page_selector */

$pageTypeIDs = $pageTypeIDs ?? [];
$pageTemplateIDs = $pageTemplateIDs ?? [];
$folders = $folders ?? [];
$items = $items ?? [];
$pages = $pages ?? [];
if ($items) {
    ?>
    <h2><?= t('Preview') ?></h2>
    <h3><?= t('Pages to import') ?></h3>
    <table class="table">
        <tr>
            <th><?= t('Title') ?></th>
            <th><?= t('Public Date') ?></th>
            <th><?= t('Link') ?></th>
            <th><?= t('Title') ?></th>
        </tr>
        <?php
        /** @var \Macareux\ContentImporter\Command\ImportListItemCommand $item */
        foreach ($items as $item) {
            ?>
            <tr>
                <td><?= h($item->getTitle()) ?></td>
                <td><?= h($item->getDateTime()) ?></td>
                <td class="text-break"><?= h($item->getLink()) ?></td>
                <td>/<?= h($item->getTopic()) ?></td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
}
if ($pages) {
    ?>
    <h3><?= t('Pagination') ?></h3>
    <ul>
        <?php
        /** @var \Macareux\ContentImporter\ListImporter\PaginationLink $page */
        foreach ($pages as $page) {
            echo sprintf('<li>%s</li>', $page->getLink());
        }
        ?>
    </ul>
<?php
}
?>
<h2><?= t('Import Settings') ?></h2>
<form id="import-setting-form" method="post" action="<?= $view->action('preview') ?>">
    <?php $token->output('list_importer') ?>
    <div class="form-group">
        <?= $form->label('url', t('URL or file path to import')) ?>
        <?= $form->text('url') ?>
    </div>
    <div class="form-group">
        <?= $form->label('root', t('Document root')) ?>
        <?= $form->text('root') ?>
    </div>
    <div class="form-group">
        <?= $form->label('title_selector', t('CSS Selector for page name')) ?>
        <?= $form->text('title_selector', ['required' => true]) ?>
    </div>
    <div class="form-group">
        <?= $form->label('date_selector', t('CSS Selector for date time')) ?>
        <?= $form->text('date_selector') ?>
    </div>
    <div class="form-group">
        <?= $form->label('date_format', t('Date time format')) ?>
        <?= $form->text('date_format') ?>
    </div>
    <div class="form-group">
        <?= $form->label('link_selector', t('CSS Selector for link')) ?>
        <?= $form->text('link_selector') ?>
    </div>
    <div class="form-group">
        <?= $form->label('topic_selector', t('CSS Selector for topic')) ?>
        <?= $form->text('topic_selector') ?>
    </div>
    <div class="form-group">
        <?= $form->label('topic_handle', t('Attribute handle for topic')) ?>
        <?= $form->text('topic_handle') ?>
    </div>
    <div class="form-group">
        <?= $form->label('file_handle', t('Attribute handle for pdf file link')) ?>
        <?= $form->text('file_handle') ?>
    </div>
    <div class="form-group">
        <?= $form->label('folder', t('Folder to import pdf files')) ?>
        <?= $form->select('folder', $folders) ?>
    </div>
    <div class="form-group">
        <?= $form->label('external_url_handle', t('Attribute handle for external link')) ?>
        <?= $form->text('external_url_handle') ?>
    </div>
    <div class="form-group">
        <?= $form->label('pagination_selector', t('CSS Selector for pagination')) ?>
        <?= $form->text('pagination_selector') ?>
    </div>
    <div class="form-group">
        <?= $form->label('parent', t('Parent Page')) ?>
        <?= $page_selector->selectPage('parent', t('Choose Parent Page')) ?>
    </div>
    <div class="form-group">
        <?= $form->label('type', t('Page Type')) ?>
        <?= $form->select('type', $pageTypeIDs) ?>
    </div>
    <div class="form-group">
        <?= $form->label('template', t('Page Template')) ?>
        <?= $form->select('template', $pageTemplateIDs) ?>
    </div>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?= $form->button('import', t('Import'), ['class' => 'btn btn-primary float-end ms-2']) ?>
            <?= $form->submit('preview', t('Preview'), ['class' => 'btn btn-secondary float-end']) ?>
        </div>
    </div>
</form>
<div style="display: none">
    <div id="ccm-dialog-import" class="ccm-ui">
        <form method="post" class="form-stacked" action="<?= $view->action('import'); ?>">
            <p><?= t('Are you sure? This action cannot be undone.') ?></p>
        </form>
        <div class="dialog-buttons">
            <button class="btn btn-secondary float-start"
                    onclick="jQuery.fn.dialog.closeTop()"><?= t('Cancel') ?></button>
            <button class="btn btn-primary float-end"
                    onclick="$('#ccm-dialog-import form').submit()"><?= t('Import') ?></button>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $('#import').on('click', function () {
            jQuery.fn.dialog.open({
                element: '#ccm-dialog-import',
                modal: true,
                width: 320,
                title: '<?=t("import List Items") ?>',
                height: 'auto'
            });
        });
        $('#ccm-dialog-import form').on('submit', function () {
            new ConcreteProgressiveOperation({
                url: $(this).attr('action'),
                data: $('#import-setting-form').serializeArray(),
                title: <?= json_encode(t('Import Links')) ?>,
                onComplete: function () {
                    window.location.href = <?=json_encode((string) $this->action('import_completed')) ?>;
                }
            });
            return false;
        });
    });
</script>