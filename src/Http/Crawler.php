<?php

namespace Macareux\ContentImporter\Http;

use Concrete\Core\Cache\Level\ExpensiveCache;
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
     * @var ExpensiveCache
     */
    protected $cache;

    /**
     * @param string $sourcePath
     * @param File $service
     */
    public function __construct(string $sourcePath, File $service, ExpensiveCache $cache)
    {
        $this->sourcePath = $sourcePath;
        $this->service = $service;
        $this->cache = $cache;
    }

    public function getContent(int $filterType, int $contentType, ?string $selector = null, ?string $attribute = null): string
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
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getHtmlByXPath(string $xpath): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->html();
    }

    /**
     * @param string $selector
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     *
     * @return string
     */
    public function getHtmlBySelector(string $selector): string
    {
        return (string) $this->getCrawlerBySelector($selector)->html();
    }

    /**
     * @param string $xpath
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getTextByXPath(string $xpath): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->text();
    }

    /**
     * @param string $selector
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     *
     * @return string
     */
    public function getTextBySelector(string $selector): string
    {
        return (string) $this->getCrawlerBySelector($selector)->text();
    }

    /**
     * @param string $xpath
     * @param string $attribute
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getAttributeValueByXPath(string $xpath, string $attribute): string
    {
        return (string) $this->getCrawlerByXPath($xpath)->attr($attribute);
    }

    /**
     * @param string $selector
     * @param string $attribute
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     *
     * @return string
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
        $contents = $this->getSource();

        return (new SymfonyCrawler($contents))->filterXPath($xpath);
    }

    protected function getCrawlerBySelector(string $selector)
    {
        $contents = $this->getSource();

        return (new SymfonyCrawler($contents))->filter($selector);
    }

    protected function getSource()
    {
        if ($this->cache->isEnabled()) {
            $item = $this->cache->getItem('/content_importer/source' . str_replace(['https:/', 'http:/'], '', $this->sourcePath));
            if ($item->isHit()) {
                return $item->get();
            }
        }

        $contents = $this->service->getContents($this->sourcePath);

        if (isset($item) && $item->isMiss()) {
            $item->set($contents);
            $item->expiresAfter(600);
            $this->cache->save($item);
        }

        return $contents;
    }
}
