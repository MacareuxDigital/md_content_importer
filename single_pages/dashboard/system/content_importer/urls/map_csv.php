<?php
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var \Concrete\Core\Validation\CSRF\Token $token */
/** @var \Concrete\Core\View\View $view */
/** @var array $headerOptions */
/** @var string $urlColumn */
/** @var string $titleColumn */
/** @var array $operatorOptions */
/** @var array $defaultFilters */
?>
<form method="post" action="<?= $view->action('submit_csv_import') ?>" id="ccm-csv-map-form">
    <?= $token->output('submit_csv_import') ?>

    <fieldset>
        <legend><?= t('Column Mapping') ?></legend>
        <div class="form-group">
            <?= $form->label('urlColumn', t('URL Column')) ?>
            <?= $form->select('urlColumn', $headerOptions, $urlColumn) ?>
        </div>
        <div class="form-group">
            <?= $form->label('titleColumn', t('Title Column')) ?>
            <?= $form->select('titleColumn', ['' => t('** None')] + $headerOptions, $titleColumn) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= t('Filters') ?></legend>
        <p class="text-muted"><?= t('Only rows matching all filters will be imported. Leave filters empty to import all rows.') ?></p>
        <div id="ccm-csv-filters">
            <?php foreach ($defaultFilters as $filter) { ?>
                <div class="row g-2 mb-2 ccm-csv-filter-row">
                    <div class="col-md-4">
                        <?= $form->select('filter_column[]', ['' => t('** Column')] + $headerOptions, $filter['column']) ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->select('filter_operator[]', $operatorOptions, $filter['operator']) ?>
                    </div>
                    <div class="col-md-5">
                        <?= $form->text('filter_value[]', $filter['value']) ?>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-secondary btn-sm" data-action="remove-filter">&times;</button>
                    </div>
                </div>
            <?php } ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" id="ccm-add-filter"><?= t('Add Filter') ?></button>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= $view->action('import_csv') ?>" class="btn btn-secondary float-start"><?= t('Back') ?></a>
            <button type="submit" class="btn btn-primary float-end"><?= t('Import') ?></button>
        </div>
    </div>
</form>

<script type="text/javascript">
    $(function () {
        var filterTemplate = $('#ccm-csv-filters .ccm-csv-filter-row').first().clone();
        filterTemplate.find('select').prop('selectedIndex', 0);
        filterTemplate.find('input').val('');

        $('#ccm-add-filter').on('click', function () {
            $('#ccm-csv-filters').append(filterTemplate.clone());
        });

        $('#ccm-csv-filters').on('click', '[data-action=remove-filter]', function () {
            var $rows = $('#ccm-csv-filters .ccm-csv-filter-row');
            if ($rows.length > 1) {
                $(this).closest('.ccm-csv-filter-row').remove();
            } else {
                $(this).closest('.ccm-csv-filter-row').find('select').prop('selectedIndex', 0);
                $(this).closest('.ccm-csv-filter-row').find('input').val('');
            }
        });

        $('#ccm-csv-map-form').on('submit', function () {
            new ConcreteProgressiveOperation({
                url: $(this).attr('action'),
                data: $(this).serializeArray(),
                title: <?= json_encode(t('Import URLs')) ?>,
                onComplete: function () {
                    window.location.href = <?= json_encode((string) $view->action('import_completed')) ?>;
                }
            });
            return false;
        });
    });
</script>
