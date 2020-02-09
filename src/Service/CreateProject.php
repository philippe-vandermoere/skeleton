<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Service;

use App\Model\Project;
use App\Model\ProjectOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class CreateProject
{
    protected Filesystem $filesystem;
    protected Project $project;
    protected ProjectOptions $projectOptions;
    protected ?LoggerInterface $logger;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function create(Project $project, ProjectOptions $projectOptions, LoggerInterface $logger = null): void
    {
        $this->project = $project;
        $this->projectOptions = $projectOptions;
        $this->logger = $logger;

        $this
            ->composer()
            ->copySkeletonFiles()
            ->removeUsedFiles()
            ->configureProject()
            ->initializeGitRepository()
            ->fixFilesOwner()
        ;
    }

    protected function composer(): self
    {
        return $this
            ->copyFile(
                new \SplFileInfo($this->projectOptions->getSkeletonDirectory() . '/composer.json'),
                new \SplFileInfo($this->project->getDirectory() . '/composer.json'),
            )
            ->executeCommand(
                [
                    'composer',
                    'install',
                    '--no-interaction',
                    '--no-progress',
                    '--ansi',
                    '--ignore-platform-reqs'
                ]
            )
            ->executeCommand(
                [
                    'composer',
                    'update',
                    '--lock',
                    '--no-interaction',
                    '--no-progress',
                    '--ansi',
                    '--ignore-platform-reqs'
                ]
            );
    }

    protected function copySkeletonFiles(): self
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->projectOptions->getSkeletonDirectory())
            ->ignoreDotFiles(false)
            ->notName('composer.json')
        ;

        foreach ($finder as $splFileInfo) {
            $this->copyFile(
                $splFileInfo,
                new \SplFileInfo($this->project->getDirectory() . '/' . $splFileInfo->getRelativePathname()),
            );
        }

        return $this->log('Copy skeleton files to project.');
    }

    protected function removeUsedFiles(): self
    {
        $this->filesystem->remove(
            [
                $this->project->getDirectory() . '/config/services.yaml',
                $this->project->getDirectory() . '/config/routes.yaml',
            ]
        );

        return $this->log('Remove unused files to project.');
    }

    protected function configureProject(): self
    {
        return $this
            ->useGitKeepInEmptyFolder()
            ->configureGitIgnore()
            ->configureDotEnv()
        ;
    }

    protected function useGitKeepInEmptyFolder(): self
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in([$this->project->getDirectory() . '/src', $this->project->getDirectory() . '/tests'])
            ->ignoreDotFiles(false)
            ->name('.gitignore')
        ;

        foreach ($finder as $splFileInfo) {
            $this->filesystem->rename(
                $splFileInfo->getPathname(),
                $splFileInfo->getPath() . '/.gitkeep',
                true
            );
        }

        return $this->log('Rename file `.gitignore` to `.gitkeep` in folder `src`, `tests`.');
    }

    protected function configureGitIgnore(): self
    {
        $this->filesystem->appendToFile(
            $this->project->getDirectory() . '/.gitignore',
            \implode(
                PHP_EOL,
                [
                    '/.idea',
                    '/docker/docker/.env',
                    ''
                ]
            )
        );

        return $this->log('Configure file `.gitignore`.');
    }

    protected function configureDotEnv(): self
    {
        \file_put_contents(
            $this->project->getDirectory(). '/.env',
            \str_replace(
                '# MESSENGER_TRANSPORT_DSN=amqp',
                'MESSENGER_TRANSPORT_DSN=amqp',
                \file_get_contents($this->project->getDirectory(). '/.env')
            )
        );

        return $this->log('Configure file `.env`.');
    }

    protected function initializeGitRepository(): self
    {
        if (true !== $this->projectOptions->isInitializeGitRepository()) {
            return $this;
        }

        $this
            ->executeCommand(['git', 'init'])
            ->executeCommand(['git', 'add', '.'])
            ->executeCommand(['git', 'commit', '-m', 'bootstrap project ' . \ucfirst($this->project->getName())])
            ->executeCommand(['make', 'git_hooks'])
        ;

        return $this->log('Initialize git Repository.');
    }

    protected function fixFilesOwner(): self
    {
        if (true !== $this->projectOptions->isFixFilesOwner()) {
            return $this;
        }

        $uid = \fileowner(\dirname($this->project->getDirectory()));
        $gid = \filegroup(\dirname($this->project->getDirectory()));

        $finder = new Finder();
        $finder
            ->in($this->project->getDirectory())
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->append([$this->project->getDirectory()])
        ;

        foreach ($finder as $splFileInfo) {
            \chown($splFileInfo->getRealPath(), $uid);
            \chgrp($splFileInfo->getRealPath(), $gid);
        }

        return  $this->log(
            sprintf(
                'Fix Files Owner (%d:%d).',
                $uid,
                $gid,
            )
        );
    }

    protected function copyFile(\SplFileInfo $source, \SplFileInfo $destination): self
    {
        $this->filesystem->mkdir($destination->getPath(), 0755);
        $destinationHandle = $destination->openFile('w');

        foreach ($source->openFile() as $content) {
            $rules = [
                '{{ skeleton_name }}' => $this->project->getName(),
                '{{ Skeleton_name }}' => \ucfirst($this->project->getName()),
                '{{ skeleton_url }}' => $this->project->getUrl(),
                '{{ skeleton_description }}' => $this->project->getDescription(),
                '{{ skeleton_repository }}' => $this->project->getOrganization() . '/' . $this->project->getName(),
            ];

            foreach ($rules as $key => $value) {
                $content = \str_replace($key, $value, $content);
            }

            $destinationHandle->fwrite($content);
        }

        $this->filesystem->chmod($destination, (true === $source->isExecutable() ? 0755 : 0644));

        return $this;
    }

    protected function executeCommand(array $command): self
    {
        $process = new Process(
            $command,
            $this->project->getDirectory(),
        );

        $process->mustRun(
            function ($type, $buffer)  {
                $this->log($buffer, 'debug');
            }
        );

        return $this;
    }

    protected function log(string $message, string $level = 'info'): self
    {
        if (true === $this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message);
        }

        return $this;
    }
}
