<?php

use Concrete\Core\Support\Facade\Url as UrlFacade;

defined('C5_EXECUTE') or die("Access Denied.");
?>
<ul class="ccm-dashboard-header-icons">
    <li>
        <a href="<?php echo (string)UrlFacade::to("/dashboard/system/content_importer/batches/logs/export"); ?>" class="ccm-hover-icon" title="<?php echo h(t('Export CSV')) ?>">
            <i class="fas fa-download" aria-hidden="true"></i>
        </a>
    </li>
</ul>
