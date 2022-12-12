<?php

namespace Concrete\Package\MdContentImporter\Controller\Element\Dashboard\Batches;

use Concrete\Core\Controller\ElementController;

class Header extends ElementController
{
    public function getElement()
    {
        return 'dashboard/batches/header';
    }
}
