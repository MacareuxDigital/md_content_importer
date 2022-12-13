<?php

namespace Macareux\ContentImporter\Transformer;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\File\Filesystem;
use Concrete\Core\File\Import\FileImporter;
use Concrete\Core\File\Import\ImportOptions;
use Concrete\Core\File\Service\File;
use Concrete\Core\File\Service\VolatileDirectory;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Request;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;

/**
 * Transformer to generate text representation for image_file attribute. e.g. "fid:1"
 */
class ImageFileAttributeTransformer implements TransformerInterface
{
    /** @var int */
    private $folderNodeID;

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

    public function getTransformerName(): string
    {
        return tc('ContentImporterTransformer', 'Image File Attribute');
    }

    public function getTransformerHandle(): string
    {
        return 'image_file';
    }

    public function supportPreview(): bool
    {
        return false;
    }

    public function transform(string $input): string
    {
        $app = Application::getFacadeApplication();
        /** @var File $fileHelper */
        $fileHelper = $app->make('helper/file');
        $filename = $fileHelper->splitFilename($input);
        /** @var VolatileDirectory $volatileDirectory */
        $volatileDirectory = $app->make(VolatileDirectory::class);
        $fullFilename = $volatileDirectory->getPath() . '/' . $filename[1] . '.' . $filename[2];
        $fileHelper->append($fullFilename, $fileHelper->getContents($input));

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
        $fv = $importer->importLocalFile($fullFilename, $filename[1] . '.' . $filename[2], $options);

        return 'fid:' . $fv->getFileID();
    }

    public function renderForm(): void
    {
        $app = Application::getFacadeApplication();

        $folders = [];
        $filesystem = new Filesystem();
        $folder = $filesystem->getRootFolder();
        if ($folder instanceof FileFolder) {
            $nodes = $folder->getHierarchicalNodesOfType(
                "file_folder",
                1,
                true,
                true,
                20
            );

            foreach ($nodes as $node) {
                /** @var FileFolder $treeNodeObject */
                $treeNodeObject = $node["treeNodeObject"];
                $folders[$treeNodeObject->getTreeNodeID()] = $treeNodeObject->getTreeNodeName();
            }
        }


        $folder = null;
        if ($this->getFolderNodeID()) {
            $folder = FileFolder::getByID($this->getFolderNodeID());
        }
        $manager = $app->make(ElementManager::class);
        $manager->get('content_importer/transformer/image_file', [
            'form' => $app->make('helper/form'),
            'folders' => $folders,
            'folder' => $folder,
        ], 'md_content_importer')->render();
    }

    public function validateRequest(Request $request): ErrorList
    {
        return new ErrorList();
    }

    public function updateFromRequest(Request $request): void
    {
        $this->setFolderNodeID($request->get('folderNodeID'));
    }

}