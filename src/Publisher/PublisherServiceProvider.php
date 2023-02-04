<?php

namespace Macareux\ContentImporter\Publisher;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use Macareux\ContentImporter\Publisher\Block\BlockPublisherManager;
use Macareux\ContentImporter\Publisher\Block\ContentBlockPublisher;

class PublisherServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BlockPublisherManager::class, function ($app) {
            $manager = new BlockPublisherManager();
            $manager->registerPublisher(new ContentBlockPublisher());

            return $manager;
        });
    }
}
