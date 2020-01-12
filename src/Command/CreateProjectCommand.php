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

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->skeletonDirectory = \dirname(__DIR__, 2);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Create project from skeleton')
            ->addArgument(
                'project-directory',
                InputArgument::REQUIRED,
                'Define the directory to create project'
            )
            ->addOption(
                'project-name',
                null,
                InputOption::VALUE_REQUIRED,
                '',
            )
            ->addOption(
                'project-url',
                null,
                InputOption::VALUE_REQUIRED,
                '',
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        if (false === \is_string($input->getOption('project-name'))) {
            $input->setOption(
                'project-name',
                $style->ask(
                    'Define the project name.',
                    \basename($input->getArgument('project-directory'))
                )
            );
        }

        if (false === \is_string($input->getOption('project-url'))) {
            $input->setOption(
                'project-url',
                $style->ask(
                    'Define the project URL.',
                    \strtolower($input->getOption('project-name')) . '.philou.dev'
                )
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style = new SymfonyStyle($input, $output);

        $this
            ->parseInput($input)
            ->copySkeletonFiles()
            ->composerInstall()
            ->removeUsedFiles()
            ->configureProject()
            ->initializeGitRepository()
        ;

        return 0;
    }

    protected function parseInput(InputInterface $input): self
    {
        $this->projectDirectory = trim($input->getArgument('project-directory'));

        if (false === $this->filesystem->isAbsolutePath($this->projectDirectory)) {
            $this->projectDirectory = $_SERVER['PWD'] . '/' . $this->projectDirectory;
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

        $this->projectName = $input->getOption('project-name') ?? \strtolower(\basename($this->projectDirectory));
        $this->projectUrl = $input->getOption('project-url') ?? \strtolower($this->projectName) . '.philou.dev';

        return $this;
    }

    protected function copySkeletonFiles(): self
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->skeletonDirectory . '/skeleton')
            ->ignoreDotFiles(false)
        ;

        foreach ($finder as $file) {
            $targetFileName = $this->projectDirectory . '/' . $file->getRelativePathname();

            $this->filesystem->mkdir(\dirname($targetFileName), 0755);
            $this->filesystem->remove($targetFileName);
            $this->filesystem->appendToFile(
                $targetFileName,
                $this->getComputeContent($file->getContents())
            );

            $this->filesystem->chmod($targetFileName, (true === $file->isExecutable() ? 0755 : 0640));
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
            ->executeCommand(['composer', 'install', '--ansi', '--no-interaction', '--no-progress'])
            ->executeCommand(['composer', 'update', '--lock', '--ansi', '--no-interaction'])
        ;
    }

    protected function removeUsedFiles(): self
    {
        $this->filesystem->remove(
            [
                $this->skeletonDirectory . '/config/services.yaml',
                $this->skeletonDirectory . '/config/routes.yaml',
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

        foreach ($finder as $file) {
            $this->filesystem->rename(
                $file->getPathname(),
                $file->getPath() . '/.gitkeep',
                true
            );
        }

        $this->style->success('Rename file `.gitignore` in folder `src` to `.gitkeep`.');

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
        $question = new ConfirmationQuestion('Do you want to initialize git repository', true);
        if (false === $this->style->askQuestion($question)) {
            return $this;
        }

        $this->filesystem->remove($this->projectDirectory . '/.git');

        $this
            ->executeCommand(['git', 'init'])
            ->executeCommand(['git', 'add', '.'])
            ->executeCommand(['git', 'commit', '-m', 'bootstrap project ' . \ucfirst($this->projectName)])
        ;

        $this->style->success('Initialize git Repository.');

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
            });

        return $this;
    }
}
