<?php

namespace Para\Command;

use Para\Configuration\GroupConfigurationInterface;
use Para\Exception\ProjectNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteProjectCommand.
 *
 * @package Para\Command
 */
class DeleteProjectCommand extends Command
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
     * DeleteProjectCommand constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger The logger.
     * @param GroupConfigurationInterface $groupConfiguration The group configuration.
     */
    public function __construct(
        LoggerInterface $logger,
        GroupConfigurationInterface $groupConfiguration
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->groupConfiguration = $groupConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('delete:project')
            ->setDescription('Deletes an existing project from the configuration.')
            ->addArgument(
                'project_name',
                InputArgument::REQUIRED,
                'The name of the project to delete.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('project_name');

        try {
            $this->groupConfiguration->removeProject($projectName);
            $this->groupConfiguration->save();
        } catch (ProjectNotFoundException $e) {
            $output->writeln('<error>The project you are trying to delete is ' .
                'not stored in the configuration.</error>', 1);

            $output->writeln('<error>Failed to delete the project "' . $projectName . '".', 1);
            return;
        }

        $output->writeln('<info>Successfully deleted the project from the configuration.</info>');
    }
}
