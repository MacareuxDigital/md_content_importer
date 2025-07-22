<?php

namespace Macareux\ContentImporter\Http;

use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\File\Service\File;
use Concrete\Core\Http\Client\Client as HttpClient;
use Concrete\Core\Package\PackageService;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use League\Url\Components\Path;
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
     * @var HttpClient
     */
    protected $client;

    /**
     * @var \Concrete\Core\Package\PackageService
     */
    protected $packageService;

    /**
     * @param string $sourcePath
     * @param File $service
     */
    public function __construct(string $sourcePath, File $service, ExpensiveCache $cache, HttpClient $client, PackageService $packageService)
    {
        $this->sourcePath = $sourcePath;
        $this->service = $service;
        $this->cache = $cache;
        $this->client = $client;
        $this->packageService = $packageService;
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
        if ($filterType === BatchItem::TYPE_FILEPATH) {
            return $this->getFilepath();
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
        return (string) $this->getCrawlerByXPath($xpath)->text('', false);
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
        return (string) $this->getCrawlerBySelector($selector)->text('', false);
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
        $path = new Path($this->getFilepath());
        $segments = $path->toArray();
        $segments = array_reverse($segments);
        foreach ($segments as $segment) {
            if (strpos($segment, 'index.') !== 0) {
                return (string) $this->service->splitFilename($segment)[1];
            }
        }

        return '';
    }

    public function getFilepath(): string
    {
        return parse_url($this->sourcePath, PHP_URL_PATH);
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

        $url = @parse_url($this->sourcePath);
        if (isset($url['scheme']) && isset($url['host'])) {
            // Get the contents from a remote URL
            $requestOptions = [];
            $package = $this->packageService->getClass('md_content_importer');
            if ($package) {
                $config = $package->getFileConfig();
                $configValue = $config->get('concrete.http.cookie');
                if ($configValue) {
                    $cookies = explode(';', $configValue);
                    $setCookies = [];
                    foreach ($cookies as $cookie) {
                        $cookie = trim($cookie);
                        if ($cookie) {
                            $setCookie = SetCookie::fromString($cookie);
                            $setCookie->setDomain($url['host']);
                            $setCookies[] = $setCookie;
                        }
                    }
                    $jar = new CookieJar(false, $setCookies);
                    $requestOptions['cookies'] = $jar;
                }
            }
            $response = $this->client->request('GET', $this->sourcePath, $requestOptions);
            $contents = $response ? $response->getBody()->getContents() : '';
        } else {
            // Get the contents from a local file
            $contents = $this->service->getContents($this->sourcePath);
        }

        if (isset($item) && $item->isMiss()) {
            $item->set($contents);
            $item->expiresAfter(600);
            $this->cache->save($item);
        }

        return $contents;
    }
}
