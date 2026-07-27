<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Url as UrlFacade;
use Macareux\ContentImporter\Entity\ImportUrl;

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
/** @var \Concrete\Core\Search\Pagination\Pagination $pagination */
/** @var string $keywords */
/** @var string $status */
/** @var string $batchId */
/** @var string $sort */
/** @var string $direction */
/** @var array $statusOptions */
/** @var array $batchOptions */
/** @var array $assignBatchOptions */
$sortUrl = static function (string $column) use ($view, $keywords, $status, $batchId, $sort, $direction): string {
    $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $query = array_filter([
        'keywords' => $keywords,
        'status' => $status,
        'batch_id' => $batchId ?: null,
        'sort' => $column,
        'direction' => $nextDirection,
    ], static function ($value): bool {
        return $value !== null && $value !== '';
    });

    return (string) $view->action('view') . '?' . http_build_query($query);
};
$sortIndicator = static function (string $column) use ($sort, $direction): string {
    if ($sort !== $column) {
        return '';
    }

    return $direction === 'asc' ? ' ↑' : ' ↓';
};
?>
<form method="get" action="<?= $view->action('view') ?>" class="mb-3">
    <?= $form->hidden('sort', $sort) ?>
    <?= $form->hidden('direction', $direction) ?>
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <?= $form->label('keywords', t('URL')) ?>
            <?= $form->text('keywords', $keywords) ?>
        </div>
        <div class="col-md-3">
            <?= $form->label('status', t('Status')) ?>
            <?= $form->select('status', $statusOptions, $status) ?>
        </div>
        <div class="col-md-3">
            <?= $form->label('batch_id', t('Batch')) ?>
            <?= $form->select('batch_id', $batchOptions, $batchId) ?>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary"><?= t('Filter') ?></button>
        </div>
    </div>
</form>

<form method="post" action="<?= $view->action('refresh_status') ?>" class="mb-3">
    <?= $token->output('refresh_status') ?>
    <button type="submit" class="btn btn-secondary btn-sm"><?= t('Refresh Status from Logs') ?></button>
</form>

<?php if (isset($pagination)) { ?>
    <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
        <?= $form->select('assign_batch_id_ui', $assignBatchOptions, '', ['class' => 'form-select', 'style' => 'max-width: 280px', 'id' => 'ccm-assign-batch-id']) ?>
        <button type="button" class="btn btn-primary btn-sm" id="ccm-assign-to-batch"><?= t('Assign to Batch') ?></button>
        <button type="button" class="btn btn-secondary btn-sm" id="ccm-unassign"><?= t('Unassign') ?></button>
        <button type="button" class="btn btn-danger btn-sm" id="ccm-remove-urls"><?= t('Remove') ?></button>
    </div>

    <div id="ccm-search-results-table">
        <table class="ccm-search-results-table table table-striped">
            <thead>
            <tr>
                <th style="width: 1%"><input type="checkbox" data-action="select-all"></th>
                <th>
                    <a href="<?= h($sortUrl('url')) ?>">
                        <?= t('Original URL') ?><?= h($sortIndicator('url')) ?>
                    </a>
                </th>
                <th><?= t('Status') ?></th>
                <th><?= t('Import Batch') ?></th>
                <th><?= t('Imported Page') ?></th>
                <th>
                    <a href="<?= h($sortUrl('import_date')) ?>">
                        <?= t('Imported Date') ?><?= h($sortIndicator('import_date')) ?>
                    </a>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            /** @var ImportUrl $importUrl */
            foreach ($pagination->getCurrentPageResults() as $importUrl) {
                $batch = $importUrl->getBatch();
                $page = $importUrl->getImportedPage();
                ?>
                <tr>
                    <td>
                        <input type="checkbox" name="url_ids[]" value="<?= (int) $importUrl->getId() ?>" class="ccm-url-select">
                    </td>
                    <td>
                        <a href="<?= h($importUrl->getUrl()) ?>" target="_blank" rel="noopener noreferrer"><?= h($importUrl->getUrl()) ?></a>
                        <?php if ($importUrl->getTitle()) { ?>
                            <div class="text-muted small"><?= h($importUrl->getTitle()) ?></div>
                        <?php } ?>
                    </td>
                    <td><?= h($importUrl->getStatusLabel()) ?></td>
                    <td>
                        <?php if ($batch) { ?>
                            <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches', 'edit_batch_basic', $batch->getId()) ?>">
                                <?= h($batch->getName()) ?>
                            </a>
                        <?php } elseif ($importUrl->isManuallyImported()) { ?>
                            <?= t('Manual') ?>
                        <?php } else { ?>
                            <span class="text-muted">—</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($page) { ?>
                            <a href="<?= h($page->getCollectionLink()) ?>" target="_blank" rel="noopener noreferrer">
                                <?= h($page->getCollectionPath() ?: $page->getCollectionName()) ?>
                            </a>
                            <div>
                                <a href="<?= h($view->action('set_imported_page', $importUrl->getId())) ?>" class="btn btn-secondary btn-sm text-nowrap">
                                    <?= t('Change') ?>
                                </a>
                            </div>
                        <?php } elseif ($importUrl->getImportedCID()) { ?>
                            <?= t('Deleted') ?>
                            <div>
                                <a href="<?= h($view->action('set_imported_page', $importUrl->getId())) ?>" class="btn btn-secondary btn-sm text-nowrap">
                                    <?= t('Set Page') ?>
                                </a>
                            </div>
                        <?php } else { ?>
                            <a href="<?= h($view->action('set_imported_page', $importUrl->getId())) ?>" class="btn btn-secondary btn-sm text-nowrap">
                                <?= t('Set Page') ?>
                            </a>
                        <?php } ?>
                    </td>
                    <td>
                        <?php
                        if ($importUrl->getImportDate()) {
                            echo h($importUrl->getImportDate()->format('Y-m-d H:i:s'));
                        } else {
                            echo '<span class="text-muted">—</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <?= $pagination->renderView('dashboard') ?>

    <form method="post" action="<?= $view->action('assign_to_batch') ?>" id="ccm-assign-form" style="display:none">
        <?= $token->output('assign_to_batch') ?>
        <input type="hidden" name="assign_batch_id" id="ccm-assign-batch-id-hidden" value="">
        <div id="ccm-assign-ids"></div>
    </form>
    <form method="post" action="<?= $view->action('unassign') ?>" id="ccm-unassign-form" style="display:none">
        <?= $token->output('unassign') ?>
        <div id="ccm-unassign-ids"></div>
    </form>
    <form method="post" action="<?= $view->action('remove_urls') ?>" id="ccm-remove-form" style="display:none">
        <?= $token->output('remove_urls') ?>
        <div id="ccm-remove-ids"></div>
    </form>

    <script type="text/javascript">
        $(function () {
            function selectedIds() {
                return $('.ccm-url-select:checked').map(function () {
                    return $(this).val();
                }).get();
            }

            function fillIds($container, ids) {
                $container.empty();
                ids.forEach(function (id) {
                    $container.append($('<input>', {type: 'hidden', name: 'url_ids[]', value: id}));
                });
            }

            $('input[data-action=select-all]').on('change', function () {
                $('.ccm-url-select').prop('checked', $(this).prop('checked'));
            });

            $('#ccm-assign-to-batch').on('click', function () {
                var ids = selectedIds();
                var batchId = $('#ccm-assign-batch-id').val();
                if (!ids.length) {
                    ConcreteAlert.error({message: <?= json_encode(t('Please select at least one URL.')) ?>});
                    return;
                }
                if (!batchId) {
                    ConcreteAlert.error({message: <?= json_encode(t('Please select a batch.')) ?>});
                    return;
                }
                $('#ccm-assign-batch-id-hidden').val(batchId);
                fillIds($('#ccm-assign-ids'), ids);
                $('#ccm-assign-form').submit();
            });

            $('#ccm-unassign').on('click', function () {
                var ids = selectedIds();
                if (!ids.length) {
                    ConcreteAlert.error({message: <?= json_encode(t('Please select at least one URL.')) ?>});
                    return;
                }
                fillIds($('#ccm-unassign-ids'), ids);
                $('#ccm-unassign-form').submit();
            });

            $('#ccm-remove-urls').on('click', function () {
                var ids = selectedIds();
                if (!ids.length) {
                    ConcreteAlert.error({message: <?= json_encode(t('Please select at least one URL.')) ?>});
                    return;
                }
                if (!window.confirm(<?= json_encode(t('Remove the selected URLs from the inventory? Assigned URLs will also be removed from their batches.')) ?>)) {
                    return;
                }
                fillIds($('#ccm-remove-ids'), ids);
                $('#ccm-remove-form').submit();
            });
        });
    </script>
<?php } ?>
