<?php

use Concrete\Core\Support\Facade\Url as UrlFacade;

defined('C5_EXECUTE') or die('Access Denied.');
?>
<div class="ccm-dashboard-header-buttons btn-group">
    <a href="<?= UrlFacade::to('/dashboard/system/content_importer/urls', 'import_csv') ?>" class="btn btn-sm btn-primary"><?= t('Import CSV') ?></a>
</div>
