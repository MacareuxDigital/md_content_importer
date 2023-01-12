<?php

namespace Macareux\ContentImporter\Entity;

use Concrete\Core\Entity\Page\Template as TemplateEntry;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Finder\Finder;

/**
 * @ORM\Entity
 * @ORM\Table(name="mdContentImporterBatches")
 */
class Batch
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $name = '';

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $sourcePath = '';

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $documentRoot = '';

    /**
     * @var int|null
     * @ORM\Column(type="integer")
     */
    private $pageTypeID;

    /**
     * @var int|null
     * @ORM\Column(type="integer")
     */
    private $pageTemplateID;

    /**
     * @var int|null
     * @ORM\Column(type="integer")
     */
    private $parentCID;

    /**
     * @var Collection<BatchItem>
     * @ORM\OneToMany(targetEntity="BatchItem", mappedBy="batch", cascade={"persist", "remove"})
     */
    private $batchItems;

    public function __construct()
    {
        $this->batchItems = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @return array
     */
    public function getSourcePathArray(): array
    {
        $sourcePaths = [];
        $sourcePath = $this->getSourcePath();
        $originalPaths = array_map('trim', explode(PHP_EOL, $sourcePath));
        foreach ($originalPaths as $path) {
            $url = parse_url($path);
            // Not external url, not a file, local path
            if (!isset($url['host']) && strpos('.htm', $path) === false) {
                $finder = new Finder();
                $finder->in($path)->files()->name(['*.html', '*.htm'])->depth('== 0');
                foreach ($finder as $file) {
                    $sourcePaths[] = $file->getRealPath();
                }
            } else {
                $sourcePaths[] = $path;
            }
        }

        return $sourcePaths;
    }

    /**
     * @param string $sourcePath
     */
    public function setSourcePath(string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @param string $documentRoot
     */
    public function setDocumentRoot(string $documentRoot): void
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * @return int|null
     */
    public function getPageTypeID(): ?int
    {
        return $this->pageTypeID;
    }

    /**
     * @return Type|null
     */
    public function getPageType(): ?Type
    {
        return Type::getByID($this->getPageTypeID());
    }

    /**
     * @param int $pageTypeID
     */
    public function setPageTypeID(int $pageTypeID): void
    {
        $this->pageTypeID = $pageTypeID;
    }

    /**
     * @return int|null
     */
    public function getPageTemplateID(): ?int
    {
        return $this->pageTemplateID;
    }

    /**
     * @return TemplateEntry|null
     */
    public function getPageTemplate(): ?TemplateEntry
    {
        return Template::getByID($this->getPageTemplateID());
    }

    /**
     * @param int $pageTemplateID
     */
    public function setPageTemplateID(int $pageTemplateID): void
    {
        $this->pageTemplateID = $pageTemplateID;
    }

    /**
     * @return int|null
     */
    public function getParentCID(): ?int
    {
        return $this->parentCID;
    }

    public function getParentPage(): Page
    {
        return Page::getByID($this->getParentCID());
    }

    /**
     * @param int $parentCID
     */
    public function setParentCID(int $parentCID): void
    {
        $this->parentCID = $parentCID;
    }

    /**
     * @return Collection<BatchItem>
     */
    public function getBatchItems()
    {
        return $this->batchItems;
    }
}
