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
class RegexTransformer implements TransformerInterface
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $replacement;

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return (string) $this->pattern;
    }

    /**
     * @param string $pattern
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }

    /**
     * @return string
     */
    public function getReplacement(): string
    {
        return (string) $this->replacement;
    }

    /**
     * @param string $replacement
     */
    public function setReplacement(string $replacement): void
    {
        $this->replacement = $replacement;
    }

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Regular Expression Search and Replace');
    }

    public function getTransformerDescription(): string
    {
        return t('Searches a string for a pattern, and replaces the matched substring with a replacement string.');
    }

    public function getTransformerHandle(): string
    {
        return 'regex';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        $replaced = preg_replace($this->getPattern(), $this->getReplacement(), $input);
        if ($replaced) {
            return $replaced;
        }

        return $input;
    }

    public function renderForm(BatchItem $batchItem): void
    {
        $app = Application::getFacadeApplication();
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/regex', [
            'form' => $app->make('helper/form'),
            'pattern' => $this->getPattern(),
            'replacement' => $this->getReplacement(),
        ], 'md_content_importer')->render();
    }

    public function validateRequest(Request $request): ErrorList
    {
        $error = new ErrorList();
        $pattern = $request->get('pattern');
        if (!$pattern) {
            $error->add(t('Please input pattern value.'));
        }
        $replacement = $request->get('replacement');
        if ($pattern) {
            preg_replace($pattern, $replacement, 'test');
            if (preg_last_error() !== PREG_NO_ERROR) {
                $error->add(preg_last_error_msg());
            }
        }

        return $error;
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setPattern($request->get('pattern'));
        $this->setReplacement($request->get('replacement'));
    }
}
