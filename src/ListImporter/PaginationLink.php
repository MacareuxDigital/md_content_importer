<?php

namespace Macareux\ContentImporter\ListImporter;

use League\Url\Components\Query;
use League\Url\Url;

class PaginationLink
{
    /**
     * @var string|null
     */
    private $link;

    /**
     * @var string|null
     */
    private $baseURL;

    /**
     * @param string|null $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @param string|null $baseURL
     */
    public function setBaseURL(string $baseURL): void
    {
        $this->baseURL = $baseURL;
    }

    public function getLink()
    {
        if (strpos($this->link, 'http') !== false) {
            $link = parse_url($this->link);
            if (!isset($link['host'])) {
                $baseURL = Url::createFromUrl($this->baseURL);
                $baseURL->setPath($link['path']);
                if (isset($link['query'])) {
                    $baseURL->setQuery(new Query($link['query']));
                }
                return $baseURL;
            }
        } else {
            return $this->baseURL . $this->link;
        }

        return $this->link;
    }
}