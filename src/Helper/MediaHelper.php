<?php declare(strict_types=1);

namespace Shopware\FixtureBundle\Helper;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Content\Media\File\FileFetcher;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MediaHelper
{
    private array $defaultFolderCache = [];

    public function __construct(
        private readonly EntityRepository $mediaRepository,
        private readonly EntityRepository $mediaFolderRepository,
        private readonly FileSaver $fileSaver,
        private readonly FileFetcher $fileFetcher,
    ) {
    }

    public function getDefaultFolder(string $entityName): ?MediaFolderEntity
    {
        if (isset($this->defaultFolderCache[$entityName])) {
            return $this->defaultFolderCache[$entityName];
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('media_folder.defaultFolder.entity', $entityName))
            ->addAssociation('defaultFolder')
            ->setLimit(1);

        $folder = $this->mediaFolderRepository
            ->search($criteria, Context::createDefaultContext())
            ->first();

        $this->defaultFolderCache[$entityName] = $folder;

        return $folder;
    }

    public function upload(string $filePath, string $mediaFolderId, ?string $fileName = null): string
    {
        $id = md5_file($filePath);

        $context = Context::createDefaultContext();
        if ($this->mediaRepository->searchIds(new Criteria([$id]), $context)->firstId() !== null) {
            return $id;
        }

        $this->mediaRepository->create([
            [
                'id' => $id,
                'mediaFolderId' => $mediaFolderId,
            ],
        ], $context);

        $fileName = $fileName ?? basename($filePath);

        $uploadedFile = $this->fileFetcher->fetchBlob(file_get_contents($filePath), pathinfo($filePath, PATHINFO_EXTENSION), mime_content_type($filePath));

        $this->fileSaver->persistFileToMedia($uploadedFile, $fileName, $id, $context);

        return $id;
    }
}
