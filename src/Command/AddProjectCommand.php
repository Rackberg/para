<?php

namespace Para\Command;

use Para\Configuration\GroupConfigurationInterface;
use Para\Factory\DecoratorFactoryInterface;
use Para\Factory\ProjectFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddProjectCommand.
 *
 * @package Para\Command
 */
class AddProjectCommand extends Command
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The group configuration.
     *
     * @var GroupConfigurationInterface
     */
    private $groupConfiguration;

    /**
     * The project factory.
     *
     * @var ProjectFactoryInterface
     */
    private $projectFactory;

    /**
     * The decorator factory.
     *
     * @var DecoratorFactoryInterface
     */
    private $decoratorFactory;

    /**
     * The config file.
     *
     * @var string
     */
    private $configFile;

    /**
     * InitCommand constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger The logger.
     * @param GroupConfigurationInterface $groupConfiguration The group configuration.
     * @param ProjectFactoryInterface $projectFactory The project factory.
     * @param DecoratorFactoryInterface $decoratorFactory The decorator factory.
     * @param string $configFile The config file.
     */
    public function __construct(
        LoggerInterface $logger,
        GroupConfigurationInterface $groupConfiguration,
        ProjectFactoryInterface $projectFactory,
        DecoratorFactoryInterface $decoratorFactory,
        string $configFile
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->groupConfiguration = $groupConfiguration;
        $this->projectFactory = $projectFactory;
        $this->decoratorFactory = $decoratorFactory;
        $this->configFile = $configFile;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('add:project')
            ->setDescription('Adds a new project.')
            ->addArgument(
                'project_name',
                InputArgument::REQUIRED,
                'The unique name of the project.'
            )
            ->addArgument(
                'project_path',
                InputArgument::REQUIRED,
                'The absolute path of the project.'
            )
            ->addArgument(
                'group_name',
                InputArgument::OPTIONAL,
                'If this argument is used, the project will be grouped using this unique group name.',
                'default'
            )

            ->addOption(
                'foreground_color',
                'fg',
                InputOption::VALUE_REQUIRED,
                'The foreground color of the text output.'
            )
            ->addOption(
                'background_color',
                'bg',
                InputOption::VALUE_REQUIRED,
                'The background color of the text output.'
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('project_name');
        $projectPath = $input->getArgument('project_path');
        $groupName = $input->getArgument('group_name');

        $foregroundColor = $input->getOption('foreground_color');
        $backgroundColor = $input->getOption('background_color');

        $this->groupConfiguration->load($this->configFile);

        $group = $this->groupConfiguration->getGroup($groupName);
        if (!$group) {
            $this->logger->error(
                'The group to add the project to is not configured.',
                ['arguments' => $input->getArguments()]
            );

            $output->writeln('<error>Failed to add the project.</error>');
            return;
        }

        $project = $this->projectFactory->getProject($projectName, $projectPath, $foregroundColor, $backgroundColor);
        $arrayDecorator = $this->decoratorFactory->getArrayDecorator($project);
        $group->addProject($arrayDecorator->asArray());

        $this->groupConfiguration->save($this->configFile);

        $output->writeln(
            sprintf(
                '<info>Successfully added the project "%s" to the group "%s".</info>',
                $projectName,
                $groupName
            )
        );
    }
}
