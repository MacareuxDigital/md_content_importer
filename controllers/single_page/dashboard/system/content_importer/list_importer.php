<?php

/** @noinspection AutoloadingIssuesInspection */

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter;

use Concrete\Core\Command\Batch\Batch;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Macareux\ContentImporter\Command\ImportListItemCommand;
use Macareux\ContentImporter\ListImporter\PaginationLink;
use Macareux\ContentImporter\Traits\FileImporterTrait;
use Symfony\Component\DomCrawler\Crawler;

class ListImporter extends DashboardPageController
{
    use FileImporterTrait;

    public function view()
    {
        $this->set('page_selector', $this->app->make('helper/form/page_selector'));

        $pageTypeIDs = ['' => t('** Select Page Type')];
        $pageTypes = Type::getList();
        /** @var Type $pageType */
        foreach ($pageTypes as $pageType) {
            $pageTypeIDs[$pageType->getPageTypeID()] = $pageType->getPageTypeDisplayName();
        }
        $this->set('pageTypeIDs', $pageTypeIDs);

        $pageTemplateIDs = ['' => t('** Select Page Template')];
        $pageTemplates = Template::getList();
        /** @var \Concrete\Core\Entity\Page\Template $pageTemplate */
        foreach ($pageTemplates as $pageTemplate) {
            $pageTemplateIDs[$pageTemplate->getPageTemplateID()] = $pageTemplate->getPageTemplateDisplayName();
        }
        $this->set('pageTemplateIDs', $pageTemplateIDs);

        $this->set('folders', $this->getFolders());
    }

    public function preview()
    {
        if (!$this->token->validate('list_importer')) {
            $this->error->add($this->token->getErrorMessage());
        }

        if (!$this->post('url')) {
            $this->error->add(t('Please input URL.'));
        }

        if (!$this->post('title_selector')) {
            $this->error->add(t('Please input CSS selector for page name.'));
        }

        if (!$this->post('date_selector')) {
            $this->error->add(t('Please input CSS selector for date time.'));
        }

        if (!$this->post('date_format')) {
            $this->error->add(t('Please input date time format.'));
        }

        if (!$this->post('link_selector')) {
            $this->error->add(t('Please input CSS selector for link.'));
        }

        if (!$this->post('file_handle')) {
            $this->error->add(t('Please input attribute handle to set attachment file.'));
        }

        if (!$this->post('external_url_handle')) {
            $this->error->add(t('Please input attribute handle to set link url.'));
        }

        if (!$this->post('parent')) {
            $this->error->add(t('Please select parent page.'));
        }

        if (!$this->post('type')) {
            $this->error->add(t('Please select page type.'));
        }

        if (!$this->post('template')) {
            $this->error->add(t('Please select pate template.'));
        }

        if (!$this->error->has()) {
            $url = $this->post('url');
            /** @var FileService $fileService */
            $fileService = $this->app->make('helper/file');
            $contents = $fileService->getContents($url);
            $crawler = new Crawler($contents);

            $items = $this->createItems($crawler);
            $this->set('items', $items);

            $pages = $this->parsePagination($crawler);
            $this->set('pages', $pages);
        }

        $this->view();
    }

    public function import()
    {
        if (!$this->token->validate('list_importer')) {
            $this->error->add($this->token->getErrorMessage());
        }

        if (!$this->error->has()) {
            $url = $this->post('url');
            /** @var FileService $fileService */
            $fileService = $this->app->make('helper/file');
            $contents = $fileService->getContents($url);
            $crawler = new Crawler($contents);
            $pages = $this->parsePagination($crawler);
            $items = $this->createItems($crawler);
            foreach ($pages as $page) {
                $_contents = $fileService->getContents($page->getLink());
                $_crawler = new Crawler($_contents);
                $_items = $this->createItems($_crawler);
                $items += $_items;
            }
            $commandBatch = Batch::create(t('Import links'), $items);

            return $this->dispatchBatch($commandBatch);
        }

        return $this->app->make(ResponseFactoryInterface::class)->error($this->error);
    }

    public function import_completed()
    {
        $this->set('message', t('Import Completed.'));
        $this->view();
    }

    /**
     * @param Crawler $crawler
     *
     * @return array|PaginationLink[]
     */
    public function parsePagination(Crawler $crawler): array
    {
        $url = $this->post('url');
        $document_root = $this->post('root');
        $pagination_selector = $this->post('pagination_selector');

        $links = [];
        if ($pagination_selector) {
            /** @var PaginationLink[] $links */
            $links = $crawler->filter($pagination_selector)
                ->filter('a')->each(function (Crawler $node, $i) use ($document_root) {
                    $link = new PaginationLink();
                    $link->setLink($node->attr('href'));
                    $link->setBaseURL((string) $document_root);

                    return $link;
                });

            foreach ($links as $i => $link) {
                if ((string) $link->getLink() === $url) {
                    unset($links[$i]);
                }
            }
        }

        return $links;
    }

    /**
     * @param Crawler $crawler
     *
     * @return array|ImportListItemCommand[]
     */
    protected function createItems(Crawler $crawler): array
    {
        $title_selector = $this->post('title_selector');
        $date_selector = $this->post('date_selector');
        $date_format = $this->post('date_format');
        $link_selector = $this->post('link_selector');
        $topic_selector = $this->post('topic_selector');
        $topic_handle = $this->post('topic_handle');
        $file_handle = $this->post('file_handle');
        $folderID = $this->post('folder');
        $external_url_handle = $this->post('external_url_handle');
        $parentID = $this->post('parent');
        $typeID = $this->post('type');
        $templateID = $this->post('template');
        $document_root = $this->post('root');

        $dates = [];
        $topics = [];
        if ($date_selector) {
            $dates = $crawler->filter($date_selector)->each(function (Crawler $node, $i) {
                return $node->innerText();
            });
        }
        $titles = $crawler->filter($title_selector)->each(function (Crawler $node, $i) {
            return $node->innerText();
        });
        $links = $crawler->filter($link_selector)->each(function (Crawler $node, $i) {
            return $node->attr('href');
        });
        if ($topic_selector) {
            $topics = $crawler->filter($topic_selector)->each(function (Crawler $node, $i) {
                return $node->innerText();
            });
        }
        if ($dates && count($dates) !== count($titles)) {
            throw new \Exception(sprintf('%d date elements found, but we found %d titles. These numbers should be same.', count($dates), count($titles)));
        }
        if ($links && count($links) !== count($titles)) {
            throw new \Exception(sprintf('%d link elements found, but we found %d titles. These numbers should be same.', count($links), count($titles)));
        }
        if ($topics && count($topics) !== count($titles)) {
            throw new \Exception(sprintf('%d topic elements found, but we found %d titles. These numbers should be same.', count($topics), count($titles)));
        }

        $items = [];
        foreach ($titles as $i => $title) {
            $item = new ImportListItemCommand();
            $item->setTitle($title);
            if ($date_format) {
                $item->setDateFormat($date_format);
            }
            if ($dates) {
                $item->setDateTime($dates[$i]);
            }
            if ($links) {
                $item->setLink($links[$i]);
            }
            if ($topics && $topic_handle) {
                $item->setTopic($topics[$i]);
                $item->setTopicHandle($topic_handle);
            }
            $item->setParentID((int) $parentID);
            $item->setTypeID((int) $typeID);
            $item->setTemplateID((int) $templateID);
            $item->setFileHandle($file_handle);
            $item->setFolderID((int) $folderID);
            $item->setExternalUrlHandle($external_url_handle);
            $item->setDocumentRoot($document_root);
            $items[] = $item;
        }

        return $items;
    }
}
