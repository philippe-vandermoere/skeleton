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
    Input\InputInterface,
    Output\OutputInterface,
    Question\ConfirmationQuestion,
    Style\SymfonyStyle
};
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PostCreateProjectCommand extends Command
{
    protected static $defaultName = 'project:post-create';

    protected Filesystem $filesystem;
    protected SymfonyStyle $style;
    protected string $projectDirectory;
    protected string $projectName;
    protected string $projectUrl;

    public function __construct()
    {
        $this->filesystem = new Filesystem();

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style = new SymfonyStyle($input, $output);

        if ('' === \Phar::running(false)) {
            $this->projectDirectory = \dirname(__DIR__, 3);
        } else {
            $this->projectDirectory = \dirname(\Phar::running(false));
        }

        $this->projectName = \strtolower(
            $this->style->ask(
                'Define the project name.',
                \basename($this->projectDirectory)
            )
        );

        $this->projectUrl = \strtolower(
            $this->style->ask(
                'Define the project URL.',
                \strtolower($this->projectName) . '.philou.dev'
            )
        );

        $this
            ->removeUsedFiles()
            ->copySkeletonFiles()
            ->configureProject()
            ->cleanSkeletonFiles()
            ->initializeGitRepository()
        ;

        return 0;
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

    protected function copySkeletonFiles(): self
    {
        $finder = new Finder();
        $finder->files()->in($this->projectDirectory . '/skeleton');

        foreach ($finder as $file) {
            $targetFileName = $this->projectDirectory . '/' . $file->getRelativePathname();

            $this->filesystem->mkdir(\dirname($targetFileName), 0755);
            $this->filesystem->remove($targetFileName);
            $this->filesystem->appendToFile(
                $targetFileName,
                $this->getComputeContent($file->getContents())
            );

            $this->filesystem->chmod($targetFileName, $file->getPerms());
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
        $finder->files()
            ->in($this->projectDirectory . '/src')
            ->name('.gitignore')
        ;

        foreach ($finder as $file) {
            $this->filesystem->rename(
                $file->getRelativePathname(),
                $file->getRelativePath() . '/.gitkeep',
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
        #sed s/#\ MESSENGER_TRANSPORT_DSN=amqp/MESSENGER_TRANSPORT_DSN=amqp/g -i "${PROJECT_FOLDER}/.env"
        $this->style->success('Configure file `.env`.');

        return $this;
    }

    protected function cleanSkeletonFiles(): self
    {
        $file = new \SplFileObject($this->projectDirectory. '/composer.json', "r");
        $content = \json_decode(
            $file->fread($file->getSize()),
            true,
            JSON_THROW_ON_ERROR
        );

        unset($content['scripts']['post-create-project-cmd']);

        $file = new \SplFileObject($this->projectDirectory. '/composer.json', "w");
        $file->fwrite(
            \json_encode(
                $content,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        $this->style->success('Remove post-create-project-cmd in `composer.json`.');

        $this->filesystem->remove(
            [
                $this->projectDirectory . '/skeleton',
                $this->projectDirectory . '/phar',
             //   $this->projectDirectory . '/post_create_project.phar',
            ]
        );

        $this->style->success('Remove skeleton temporary files.');

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

        $process->mustRun();

        return $this;
    }
}
