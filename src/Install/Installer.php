<?php

namespace Macareux\ContentImporter\Install;

use Concrete\Core\Entity\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single;

final class Installer
{
    /**
     * @var Package
     */
    protected $package;

    /**
     * @param Package $package
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function install()
    {
        $this->installSinglePages();
    }

    private function installSinglePages(): void
    {
        $singlePages = [
            '/dashboard/system/content_importer' => 'Content Importer',
            '/dashboard/system/content_importer/batches' => 'Batches',
        ];
        foreach ($singlePages as $path => $name) {
            $this->installSinglePage($path, $name);
        }
    }

    private function installSinglePage(string $path, string $name): void
    {
        $page = Page::getByPath($path);
        if (!$page || $page->isError()) {
            $page = Single::add($path, $this->package);
            $page->updateCollectionName($name);
        }
    }
}
