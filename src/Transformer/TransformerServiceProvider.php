<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(TransformerManager::class, function ($app) {
            $manager = new TransformerManager();
            $manager->registerTransformer(new ReplaceTransformer());
            $manager->registerTransformer(new RegexTransformer());
            $manager->registerTransformer(new TopicsAttributeTransformer());
            $manager->registerTransformer(new ImageFileAttributeTransformer());
            $manager->registerTransformer(new ImageFileContentTransformer());
            $manager->registerTransformer(new DateTimeTransformer());

            return $manager;
        });
    }
}
