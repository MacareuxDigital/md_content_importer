<?php

namespace Macareux\ContentImporter\Traits;

use Carbon\CarbonImmutable;
use Concrete\Core\Entity\File\Version;
use Concrete\Core\File\Filesystem;
use Concrete\Core\File\Import\FileImporter;
use Concrete\Core\File\Import\ImportException;
use Concrete\Core\File\Import\ImportOptions;
use Concrete\Core\File\Service\File;
use Concrete\Core\File\Service\VolatileDirectory;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Tree\Node\Type\FileFolder;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\ContentImporter\Entity\ImportFileLog;
use Macareux\ContentImporter\Repository\ImportFileLogRepository;

trait FileImporterTrait
{
    /**
     * @var int|null
     */
    private $folderNodeID;

    /**
     * @var string|null
     */
    private $documentRoot;

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

    /**
     * @param $file
     *
     * @throws \Concrete\Core\File\Import\ImportException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return Version
     */
    public function importFile($file): Version
    {
        if ($this->getDocumentRoot()) {
            $file = $this->getDocumentRoot() . parse_url($file, PHP_URL_PATH);
        }

        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        /** @var ImportFileLogRepository $logRepository */
        $logRepository = $entityManager->getRepository(ImportFileLog::class);
        $log = $logRepository->findOneByOriginal($file);
        if ($log) {
            $imported = $log->getImportedFile();
            if ($imported) {
                return $imported->getApprovedVersion();
            }
        }

        /** @var File $fileHelper */
        $fileHelper = $app->make('helper/file');
        $fileContent = $fileHelper->getContents($file);
        if (!$fileContent) {
            throw ImportException::fromErrorCode(ImportException::E_FILE_INVALID);
        }

        $filename = $fileHelper->splitFilename($file);
        /** @var VolatileDirectory $volatileDirectory */
        $volatileDirectory = $app->make(VolatileDirectory::class);
        $fullFilename = $volatileDirectory->getPath() . '/' . $filename[1] . '.' . $filename[2];
        $fileHelper->append($fullFilename, $fileContent);

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
        $successLog = new ImportFileLog();
        $successLog->setImportedFID($fv->getFileID());
        $successLog->setImportDate(CarbonImmutable::now());
        $successLog->setOriginal($file);
        $entityManager->persist($successLog);
        $entityManager->flush();

        return $fv;
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
                $folders[$treeNodeObject->getTreeNodeID()] = $treeNodeObject->getTreeNodeDisplayPath();
            }
        }

        return $folders;
    }
}
