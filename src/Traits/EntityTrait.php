<?php

namespace Macareux\ContentImporter\Traits;

use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;

trait EntityTrait
{
    public function getAll(string $class): array
    {
        return $this->getRepository($class)->findAll();
    }

    public function getEntry(string $class, int $id)
    {
        return $this->getRepository($class)->find($id);
    }

    private function getRepository(string $class)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);

        return $entityManager->getRepository($class);
    }
}