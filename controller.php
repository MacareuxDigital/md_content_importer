<?php

/** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter;

use Concrete\Core\Foundation\Service\ProviderList;
use Concrete\Core\Package\Package;
use Macareux\ContentImporter\Install\Installer;
use Macareux\ContentImporter\Publisher\PublisherServiceProvider;
use Macareux\ContentImporter\Transformer\TransformerServiceProvider;

class Controller extends Package
{
    protected $appVersionRequired = '9.0.0';

    protected $pkgHandle = 'md_content_importer';

    protected $pkgVersion = '0.7.0';

    protected $pkgAutoloaderRegistries = [
        'src' => '\Macareux\ContentImporter',
    ];

    public function getPackageName()
    {
        return t('Macareux Content Importer');
    }

    public function getPackageDescription()
    {
        return t('A Concrete CMS package to import contents from external resources.');
    }

    public function install()
    {
        $pkg = parent::install();

        /** @var Installer $installer */
        $installer = $this->app->make(Installer::class, ['package' => $pkg]);
        $installer->install();

        return $pkg;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade()
    {
        /** @var Installer $installer */
        $installer = $this->app->make(Installer::class, ['package' => $this->getPackageEntity()]);
        $installer->install();

        parent::upgrade();
    }

    public function on_start()
    {
        $this->registerAutoloader();

        /** @var ProviderList $serviceProviderList */
        $serviceProviderList = $this->app->make(ProviderList::class);
        $serviceProviderList->registerProvider(TransformerServiceProvider::class);
        $serviceProviderList->registerProvider(PublisherServiceProvider::class);
    }

    private function registerAutoloader()
    {
        require_once $this->getPackagePath() . '/vendor/autoload.php';
    }
}
