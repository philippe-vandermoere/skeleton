<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class ProjectOptions
{
    protected string $skeletonDirectory;
    protected bool $initializeGitRepository;
    protected bool $fixFilesOwner;
    protected int $uid;
    protected int $gid;

    public function __construct(
        string $skeletonDirectory,
        bool $initializeGitRepository = true,
        bool $fixFilesOwner = false
    ) {
        if (false === \is_dir($skeletonDirectory)) {
            throw new \RuntimeException(
                sprintf(
                    'The sheleton directory `%s` is not valid directory.',
                    $skeletonDirectory
                )
            );
        }

        $this->skeletonDirectory = $skeletonDirectory;
        $this->initializeGitRepository = $initializeGitRepository;
        $this->fixFilesOwner = $fixFilesOwner;
    }

    public function getSkeletonDirectory(): string
    {
        return $this->skeletonDirectory;
    }

    public function isInitializeGitRepository(): bool
    {
        return $this->initializeGitRepository;
    }

    public function isFixFilesOwner(): bool
    {
        return $this->fixFilesOwner;
    }
}
