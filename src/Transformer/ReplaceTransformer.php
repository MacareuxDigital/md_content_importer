<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Macareux\ContentImporter\Entity\BatchItem;

/**
 * Transformer uses str_replace.
 */
class ReplaceTransformer implements TransformerInterface
{
    private $search;

    private $replace;

    /**
     * @return mixed
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param mixed $search
     */
    public function setSearch($search): void
    {
        $this->search = $search;
    }

    /**
     * @return mixed
     */
    public function getReplace()
    {
        return $this->replace;
    }

    /**
     * @param mixed $replace
     */
    public function setReplace($replace): void
    {
        $this->replace = $replace;
    }

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Search and Replace');
    }

    public function getTransformerDescription(): string
    {
        return t('Simple search and replace string.');
    }

    public function getTransformerHandle(): string
    {
        return 'replace';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        return str_replace($this->getSearch(), $this->getReplace(), $input);
    }

    public function renderForm(BatchItem $batchItem): void
    {
        $app = Application::getFacadeApplication();
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/replace', [
            'form' => $app->make('helper/form'),
            'search' => $this->getSearch(),
            'replace' => $this->getReplace(),
        ], 'md_content_importer')->render();
    }

    public function validateRequest(Request $request): ErrorList
    {
        $error = new ErrorList();
        if (!$request->get('search')) {
            $error->add(t('Please input search value.'));
        }

        return $error;
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setSearch($request->get('search'));
        $this->setReplace($request->get('replace'));
    }
}
