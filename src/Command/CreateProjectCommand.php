<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Command;

use App\Logger\SymfonyConsole;
use App\Model\Project;
use App\Model\ProjectOptions;
use App\Service\CreateProject;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Question\ConfirmationQuestion,
    Style\SymfonyStyle
};
use Symfony\Component\Filesystem\Filesystem;

class CreateProjectCommand extends Command
{
    protected static $defaultName = 'project:create';
    protected CreateProject $createProject;

    public function __construct(CreateProject $createProject)
    {
        $this->createProject = $createProject;
        parent::__construct();
    }

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
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the directory to create project',
                \getcwd()
            )
            ->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the project URL'
            )
            ->addOption(
                'organization',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the project organization'
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the project description',
                ''
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
        $style = new SymfonyStyle($input, $output);

        if (null === $input->getArgument('name')) {
            $input->setArgument(
                'name',
                $style->ask('Define the project name ?')
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
            if (null === $input->getOption($optionDefinition->getName())) {
                if (true === $optionDefinition->isValueRequired()) {
                    $input->setOption(
                        $optionDefinition->getName(),
                        $style->ask(
                            $optionDefinition->getDescription() . ' ?',
                            $optionDefinition->getDefault(),
                        )
                    );
                } elseif (false === $optionDefinition->acceptValue()) {
                    $input->setOption(
                        $optionDefinition->getName(),
                        $style->askQuestion(
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
        $projectName = $input->getArgument('name') ?? '';
        $projectDirectory = $input->getOption('directory') . '/' . $projectName;

        if (true === $input->getOption('delete-project-directory')) {
            (new Filesystem())->remove($projectDirectory);
        }

        $this->createProject->create(
            new Project(
                $projectName,
                $projectDirectory,
                $input->getOption('url'),
                $input->getOption('organization'),
                $input->getOption('description'),
            ),
            new ProjectOptions(
                \dirname(__DIR__, 2) . '/skeleton',
                (false === $input->getOption('no-initialize-git')),
                $input->getOption('fix-files-owner')
            ),
            new SymfonyConsole(new SymfonyStyle($input, $output))
        );

        return 0;
    }
}
