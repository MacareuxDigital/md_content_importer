<?php

namespace Macareux\ContentImporter\Traits;

use Concrete\Core\Page\Type\Type;
use Concrete\Core\Permission\Checker;

trait PermissionCheckerTrait
{
    protected function canAddPageType(Type $type): bool
    {
        return (new Checker($type))->canAddPageType();
    }
}