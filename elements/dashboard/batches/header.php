<?php

use Concrete\Core\Support\Facade\Url as UrlFacade;

defined('C5_EXECUTE') or die("Access Denied.");
?>
<div class="ccm-dashboard-header-buttons btn-group">
    <a href="<?= UrlFacade::to('/dashboard/system/content_importer/batches', 'add_batch') ?>" class="btn btn-sm btn-primary"><?=t('Add Batch')?></a>
</div>




