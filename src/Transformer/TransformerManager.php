<?php

namespace Macareux\ContentImporter\Transformer;

class TransformerManager
{
    private $transformers = [];

    public function registerTransformer(TransformerInterface $transformer): void
    {
        $handle = $transformer->getTransformerHandle();
        if (!isset($this->transformers[$handle])) {
            $this->transformers[$handle] = $transformer;
        }
    }

    /**
     * @return TransformerInterface[]
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }

    public function getTransformer(string $handle): ?TransformerInterface
    {
        foreach ($this->getTransformers() as $transformer) {
            if ($transformer->getTransformerHandle() === $handle) {
                return $transformer;
            }
        }

        return null;
    }
}