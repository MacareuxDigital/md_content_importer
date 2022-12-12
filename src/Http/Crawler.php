<?php

namespace Macareux\ContentImporter\Http;

use Concrete\Core\File\Service\File;
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

    /**
     * @param string $xpath
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getByXPath(string $xpath): string
    {
        $contents = $this->service->getContents($this->sourcePath);
        $crawler = new SymfonyCrawler($contents);
        $crawler = $crawler->filterXPath($xpath);

        return $crawler->html();
    }

    /**
     * @param string $selector
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws SyntaxErrorException
     */
    public function getBySelector(string $selector): string
    {
        $contents = $this->service->getContents($this->sourcePath);
        $crawler = new SymfonyCrawler($contents);
        $crawler = $crawler->filter($selector);

        return $crawler->html();
    }
}