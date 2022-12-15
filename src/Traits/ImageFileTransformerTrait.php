<?php

namespace Macareux\ContentImporter\Traits;

use Concrete\Core\Entity\File\Version;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\File\Filesystem;
use Concrete\Core\File\Import\FileImporter;
use Concrete\Core\File\Import\ImportOptions;
use Concrete\Core\File\Service\File;
use Concrete\Core\File\Service\VolatileDirectory;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;

trait ImageFileTransformerTrait
{
    /**
     * @var int|null
     */
    private $folderNodeID;

    /**
     * @var string|null
     */
    private $documentRoot;

    public function supportPreview(): bool
    {
        return false;
    }

    /**
     * @return int
     */
    public function getFolderNodeID(): ?int
    {
        return $this->folderNodeID;
    }

    /**
     * @param int $folderNodeID
     */
    public function setFolderNodeID(int $folderNodeID): void
    {
        $this->folderNodeID = $folderNodeID;
    }

    /**
     * @return string|null
     */
    public function getDocumentRoot(): ?string
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

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function importFile($file): Version
    {
        if ($this->getDocumentRoot()) {
            $file = $this->getDocumentRoot() . $file;
        }

        $app = Application::getFacadeApplication();
        /** @var File $fileHelper */
        $fileHelper = $app->make('helper/file');
        $filename = $fileHelper->splitFilename($file);
        /** @var VolatileDirectory $volatileDirectory */
        $volatileDirectory = $app->make(VolatileDirectory::class);
        $fullFilename = $volatileDirectory->getPath() . '/' . $filename[1] . '.' . $filename[2];
        $fileHelper->append($fullFilename, $fileHelper->getContents($file));

        /** @var FileImporter $importer */
        $importer = $app->make(FileImporter::class);
        /** @var ImportOptions $options */
        $options = $app->make(ImportOptions::class);
        $options->setSkipThumbnailGeneration(true);
        if ($this->getFolderNodeID()) {
            $folder = FileFolder::getByID($this->getFolderNodeID());
            if ($folder) {
                $options->setImportToFolder($folder);
            }
        }

        return $importer->importLocalFile($fullFilename, $filename[1] . '.' . $filename[2], $options);
    }

    private function getFolders(): array
    {
        $folders = [];
        $filesystem = new Filesystem();
        $folder = $filesystem->getRootFolder();
        if ($folder instanceof FileFolder) {
            $nodes = $folder->getHierarchicalNodesOfType(
                'file_folder',
                1,
                true,
                true,
                20
            );

            foreach ($nodes as $node) {
                /** @var FileFolder $treeNodeObject */
                $treeNodeObject = $node['treeNodeObject'];
                $folders[$treeNodeObject->getTreeNodeID()] = $treeNodeObject->getTreeNodeName();
            }
        }

        return $folders;
    }
}
