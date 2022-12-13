<?php

namespace Macareux\ContentImporter\Http;

use Concrete\Core\File\Service\File;
use Macareux\ContentImporter\Entity\BatchItem;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class Crawler
{
    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var File
     */
    protected $service;

    /**
     * @param string $sourcePath
     * @param File $service
     */
    public function __construct(string $sourcePath, File $service)
    {
        $this->sourcePath = $sourcePath;
        $this->service = $service;
    }

    public function getContent(int $filterType, int $contentType, string $selector, string $attribute = null): string
    {
        if ($filterType === BatchItem::TYPE_XPATH) {
            if ($contentType === BatchItem::CONTENT_HTML) {
                return $this->getHtmlByXPath($selector);
            }
            if ($contentType === BatchItem::CONTENT_TEXT) {
                return $this->getTextByXPath($selector);
            }
            if ($contentType === BatchItem::CONTENT_ATTRIBUTE && $attribute) {
                return $this->getAttributeValueByXPath($selector, $attribute);
            }
        }
        if ($filterType === BatchItem::TYPE_SELECTOR) {
            if ($contentType === BatchItem::CONTENT_HTML) {
                return $this->getHtmlBySelector($selector);
            }
            if ($contentType === BatchItem::CONTENT_TEXT) {
                return $this->getTextBySelector($selector);
            }
            if ($contentType === BatchItem::CONTENT_ATTRIBUTE && $attribute) {
                return $this->getAttributeValueBySelector($selector, $attribute);
            }
        }
        if ($filterType === BatchItem::TYPE_FILENAME) {
            return $this->getFilename();
        }

        return '';
    }

    /**
     * @param string $xpath
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getHtmlByXPath(string $xpath): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->html();
    }

    /**
     * @param string $selector
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     */
    public function getHtmlBySelector(string $selector): string
    {
        return (string) $this->getCrawlerBySelector($selector)->html();
    }

    /**
     * @param string $xpath
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getTextByXPath(string $xpath): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->text();
    }

    /**
     * @param string $selector
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     */
    public function getTextBySelector(string $selector): string
    {
        return (string) $this->getCrawlerBySelector($selector)->text();
    }

    /**
     * @param string $xpath
     * @param string $attribute
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getAttributeValueByXPath(string $xpath, string $attribute): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->attr($attribute);
    }

    /**
     * @param string $selector
     * @param string $attribute
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     */
    public function getAttributeValueBySelector(string $selector, string $attribute): string
    {
        return (string) $this->getCrawlerBySelector($selector)->attr($attribute);
    }

    public function getFilename(): string
    {
        return (string) $this->service->splitFilename($this->sourcePath)[1];
    }

    protected function getCrawlerByXPath(string $xpath)
    {
        $contents = $this->service->getContents($this->sourcePath);

        return (new SymfonyCrawler($contents))->filterXPath($xpath);
    }

    protected function getCrawlerBySelector(string $selector)
    {
        $contents = $this->service->getContents($this->sourcePath);

        return (new SymfonyCrawler($contents))->filter($selector);
    }
}