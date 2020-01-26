<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\{
    Command\Command,
    Exception\InvalidArgumentException,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Question\ConfirmationQuestion,
    Style\SymfonyStyle
};
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class CreateProjectCommand extends Command
{
    protected static $defaultName = 'project:create';

    protected Filesystem $filesystem;
    protected SymfonyStyle $style;
    protected string $skeletonDirectory;
    protected string $projectDirectory;
    protected string $projectName;
    protected string $projectUrl;
    protected bool $initializeGit;
    protected bool $fixFilesOwner;
    protected int $uid;
    protected int $gid;

    protected function configure()
    {
        $this
            ->setDescription('Create project from skeleton.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Define the project name'
            )
            ->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the project URL'
            )
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the directory to create project',
                \getcwd()
            )
            ->addOption(
                'delete-project-directory',
                null,
                InputOption::VALUE_NONE,
                'Delete the project directory if exist'
            )
            ->addOption(
                'no-initialize-git',
                null,
                InputOption::VALUE_NONE,
                'Disable initialize GIT repository'
            )
            ->addOption(
                'fix-files-owner',
                null,
                InputOption::VALUE_NONE,
                'Fix Files owner'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->style = new SymfonyStyle($input, $output);

        if (null === $input->getArgument('name')) {
            $input->setArgument(
                'name',
                $this->style->ask('Define the project name ?')
            );
        }

        $optionsDefinition = \array_filter(
            $this->getDefinition()->getOptions(),
            function (InputOption $option): bool {
                foreach ($this->getApplication()->getDefinition()->getOptions() as $applicationOption) {
                    if (true === $option->equals($applicationOption)) {
                        return false;
                    }
                }

                return true;
            }
        );

        foreach ($optionsDefinition as $optionDefinition) {
            if (null === $input->getOption($optionDefinition->getName())
                || $optionDefinition->getDefault() === $input->getOption($optionDefinition->getName())
            ) {
                if (true === $optionDefinition->isValueRequired()) {
                    $input->setOption(
                        $optionDefinition->getName(),
                        $this->style->ask(
                            $optionDefinition->getDescription() . ' ?',
                            $optionDefinition->getDefault(),
                        )
                    );
                } elseif (false === $optionDefinition->acceptValue()) {
                    $input->setOption(
                        $optionDefinition->getName(),
                        $this->style->askQuestion(
                            new ConfirmationQuestion(
                                $optionDefinition->getDescription(). ' ?',
                                $optionDefinition->getDefault()
                            )
                        )
                    );
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->filesystem ??= new Filesystem();
        $this->style ??= new SymfonyStyle($input, $output);

        $this
            ->parseInput($input)
            ->copySkeletonFiles()
            ->composerInstall()
            ->removeUsedFiles()
            ->configureProject()
            ->initializeGitRepository()
            ->fixFilesOwner()
        ;

        return 0;
    }

    protected function parseInput(InputInterface $input): self
    {
        if (1 !== \preg_match('/[a-zA-Z0-9\-_]{4,}/', $input->getArgument('name') ?? '')) {
            throw new InvalidArgumentException(
                \sprintf(
                    'The project name `%s` must be respect regex `%s`.',
                    $input->getArgument('name') ?? '',
                    '[a-zA-Z0-9-_]{4,}'
                )
            );
        }

        if (false === \filter_var($input->getOption('url'), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'The project url `%s` must be a valid domain.',
                    $input->getOption('url') ?? ''
                )
            );
        }

        $this->skeletonDirectory = \dirname(__DIR__, 2) . '/skeleton';
        $this->projectName = $input->getArgument('name');
        $this->projectDirectory = $input->getOption('directory') . '/' . $this->projectName;
        $this->projectUrl = $input->getOption('url');
        $this->initializeGit = (false === $input->getOption('no-initialize-git'));

        $this->uid = \fileowner($input->getOption('directory'));
        $this->gid = \filegroup($input->getOption('directory'));
        $this->fixFilesOwner = $input->getOption('fix-files-owner');

        if (true === $input->getOption('delete-project-directory')) {
            $this->filesystem->remove($this->projectDirectory);
        }

        $this->filesystem->mkdir($this->projectDirectory, 0755);

        if (0 !== (new Finder())->files()->in($this->projectDirectory)->ignoreDotFiles(false)->count()) {
            throw new \RuntimeException(
                sprintf(
                    'The project directory `%s` is not empty.',
                    $this->projectDirectory
                )
            );
        }

        return $this;
    }

    protected function copySkeletonFiles(): self
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->skeletonDirectory)
            ->ignoreDotFiles(false)
        ;

        foreach ($finder as $splFileInfo) {
            $targetFileName = $this->projectDirectory . '/' . $splFileInfo->getRelativePathname();

            $this->filesystem->mkdir(\dirname($targetFileName), 0755);
            $this->filesystem->remove($targetFileName);
            $this->filesystem->appendToFile(
                $targetFileName,
                $this->getComputeContent($splFileInfo->getContents())
            );

            $this->filesystem->chmod($targetFileName, (true === $splFileInfo->isExecutable() ? 0755 : 0640));
        }

        $this->style->success('Copy skeleton files to project.');

        return $this;
    }

    protected function getComputeContent(string $content): string
    {
        $rules = [
            'skeleton_name' => $this->projectName,
            'Skeleton_name' => \ucfirst($this->projectName),
            'skeleton_url' => $this->projectUrl,
        ];

        foreach ($rules as $key => $value) {
            $content = \str_replace($key, $value, $content);
        }

        return $content;
    }

    protected function composerInstall(): self
    {
        return $this
            ->executeCommand(['composer', 'install', '--no-interaction', '--no-progress', '--ansi'])
            ->executeCommand(['composer', 'update', '--lock', '--no-interaction', '--no-progress', '--ansi'])
        ;
    }

    protected function removeUsedFiles(): self
    {
        $this->filesystem->remove(
            [
                $this->projectDirectory . '/config/services.yaml',
                $this->projectDirectory . '/config/routes.yaml',
            ]
        );

        $this->style->success('Remove unused files to project.');

        return $this;
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
            ->in([$this->projectDirectory . '/src', $this->projectDirectory . '/tests'])
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

        $this->style->success('Rename file `.gitignore` in folder `src`, `tests` to `.gitkeep`.');

        return $this;
    }

    protected function configureGitIgnore(): self
    {
        $this->filesystem->appendToFile(
            $this->projectDirectory . '/.gitignore',
            \implode(
                PHP_EOL,
                [
                    '/.idea',
                    '/docker/.env',
                    ''
                ]
            )
        );

        $this->style->success('Configure file `.gitignore`.');

        return $this;
    }

    protected function configureDotEnv(): self
    {
        \file_put_contents(
            $this->projectDirectory. '/.env',
            \str_replace(
                '# MESSENGER_TRANSPORT_DSN=amqp',
                'MESSENGER_TRANSPORT_DSN=amqp',
                \file_get_contents($this->projectDirectory. '/.env')
            )
        );

        $this->style->success('Configure file `.env`.');

        return $this;
    }

    protected function initializeGitRepository(): self
    {
        if (false === $this->initializeGit) {
            return $this;
        }

        $this
            ->executeCommand(['git', 'init'])
            ->executeCommand(['git', 'add', '.'])
            ->executeCommand(['git', 'commit', '-m', 'bootstrap project ' . \ucfirst($this->projectName)])
        ;

        $this->style->success('Initialize git Repository.');

        return $this;
    }

    protected function fixFilesOwner(): self
    {
        if (false === $this->fixFilesOwner) {
            return $this;
        }

        $finder = new Finder();
        $finder
            ->in($this->projectDirectory)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->append([$this->projectDirectory])
        ;

        foreach ($finder as $splFileInfo) {
            \chown($splFileInfo->getRealPath(), $this->uid);
            \chgrp($splFileInfo->getRealPath(), $this->gid);
        }

        $this->style->success(sprintf('Fix Files Owner (%d:%d).', $this->uid, $this->gid));

        return $this;
    }

    protected function executeCommand(array $command): self
    {
        $process = new Process(
            $command,
            $this->projectDirectory,
        );

        $process->mustRun(
            function ($type, $buffer)  {
                $this->style->write($buffer);
            }
        );

        return $this;
    }
}
