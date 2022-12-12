<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->app->singleton(TransformerManager::class, function ($app) {
            $manager = new TransformerManager();
            $manager->registerTransformer(new ReplaceTransformer());

            return $manager;
        });
    }
}