<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;

class DateTimeTransformer implements TransformerInterface
{
    /**
     * @var string|null
     */
    private $format;

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param string|null $format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Date Time Format Converter');
    }

    public function getTransformerHandle(): string
    {
        return 'date_time';
    }

    public function supportPreview(): bool
    {
        return true;
    }

    public function transform(string $input): string
    {
        $dateTime = null;
        $format = $this->getFormat();
        if ($format) {
            $dateTime = \DateTimeImmutable::createFromFormat($format, $input);
        }

        if (!is_object($dateTime)) {
            $dateTime = new \DateTimeImmutable();
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    public function renderForm(): void
    {
        $app = Application::getFacadeApplication();
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/date_time', [
            'form' => $app->make('helper/form'),
            'format' => $this->getFormat(),
        ], 'md_content_importer')->render();
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFormat($request->get('format'));
    }
}
