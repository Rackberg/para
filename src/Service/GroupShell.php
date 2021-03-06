<?php

namespace Para\Service;

use Para\Event\BeforeShellCommandExecutionEvent;
use Para\Event\ShellEvents;
use Para\Factory\ProcessFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class GroupShell.
 *
 * @package Para\Service
 */
class GroupShell implements InteractiveShellInterface
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The application.
     *
     * @var Application
     */
    private $application;

    /**
     * The process factory.
     *
     * @var \Para\Factory\ProcessFactoryInterface
     */
    private $processFactory;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * The console input.
     *
     * @var InputInterface
     */
    private $input;

    /**
     * The console output.
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * The history shell manager.
     *
     * @var HistoryShellManagerInterface
     */
    private $historyShellManager;

    /**
     * The number of prompts shown in the shell.
     *
     * @var int
     */
    private $promptCounter = 0;

    /**
     * GroupShell constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger The logger.
     * @param \Symfony\Component\Console\Application $application The application.
     * @param \Para\Factory\ProcessFactoryInterface $processFactory The process factory.
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher The event dispatcher.
     * @param \Para\Service\HistoryShellManagerInterface $historyShellManager The history shell manager.
     * @param \Symfony\Component\Console\Input\InputInterface $input The console input.
     * @param \Symfony\Component\Console\Output\OutputInterface $output The console output.
     */
    public function __construct(
        LoggerInterface $logger,
        Application $application,
        ProcessFactoryInterface $processFactory,
        EventDispatcherInterface $dispatcher,
        HistoryShellManagerInterface $historyShellManager,
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->logger = $logger;
        $this->application = $application;
        $this->processFactory = $processFactory;
        $this->dispatcher = $dispatcher;
        $this->historyShellManager = $historyShellManager;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Starts a new shell process.
     *
     * @param $groupName
     * @param array $exludedProjects
     * @param string $historyFile
     */
    public function run($groupName, array $exludedProjects = [], $historyFile = null)
    {
        // Load the persisted shell command history.
        if ($historyFile) {
            $this->historyShellManager->getHistory()->loadHistory($historyFile);
        }

        // Set the prompt.
        $this->historyShellManager->setPrompt($this->getPrompt($groupName));

        // Disable auto exit from shell.
        $this->application->setAutoExit(false);

        // Show the welcome message.
        $this->output->writeln($this->getHeader($groupName, $exludedProjects));

        while (true) {
            // Add a new line to separate the prompts from each other.
            if ($this->promptCounter > 0 && !empty($cmd)) {
                $this->output->write("\n");
            }

            // Read the command the user enters.
            $cmd = $this->readline($groupName);
            $this->promptCounter++;

            if (false === $cmd) {
                $this->output->writeln("\n");

                break;
            }

            // Add the command to the history.
            if (!empty($cmd) && $cmd != 'exit') {
                $this->historyShellManager->getHistory()->addCommand($cmd);
            }

            // Create an event.
            $event = new BeforeShellCommandExecutionEvent($cmd);

            // Dispatch an event to do something with the command string before running it.
            $this->dispatcher->dispatch(ShellEvents::BEFORE_SHELL_COMMAND_EXECUTION_EVENT, $event);

            if ($event->getCommand() == 'exit') {
                $this->application->setAutoExit(true);
                return;
            }

            if ($exludedProjects != []) {
                foreach ($exludedProjects as &$exludedProject) {
                    $exludedProject = '-x '.$exludedProject;
                }
            }

            $command = new StringInput(
                sprintf(
                    'execute %s "%s"'.($exludedProjects != [] ? join(
                        ' ',
                        $exludedProjects
                    ) : ''),
                    $groupName,
                    $event->getCommand()
                )
            );

            $ret = $this->application->run($command, $this->output);

            if (0 !== $ret) {
                $this->output->writeln(
                    sprintf(
                        '<error>The command terminated with an error status (%s)</error>',
                        $ret
                    )
                );
            }
        }
    }

    /**
     * Returns the shell header.
     *
     * @param string $groupName The group name.
     * @param array $excludedProjects The excluded projects.
     *
     * @return string The header string.
     */
    private function getHeader($groupName, array $excludedProjects = [])
    {
        if ($excludedProjects != []) {
            $ignoredProjects = '';
            foreach ($excludedProjects as $project) {
                $ignoredProjects .= '<comment>' . $project . '</comment>, ';
            }
            $ignoredProjects = ' except for the projects ' . substr($ignoredProjects, 0, strlen($ignoredProjects) - 2) . '.';
        } else {
            $ignoredProjects = '.';
        }

        return <<<EOF

Welcome to the <info>Para</info> shell (<comment>{$this->application->getVersion()}</comment>).

All commands you type in will be executed for each project configured in the group <comment>{$groupName}</comment>{$ignoredProjects}

THE SHORTCUT <comment>(ctrl + C)</comment> HAS BEEN <comment>DISABLED</comment> !

To exit the shell, type <comment>exit</comment>.

EOF;
    }

    /**
     * Reads a single line from standard input.
     *
     * @param string $groupName The name of the group.
     *
     * @return string The single line from standard input.
     */
    private function readline($groupName)
    {
        $this->output->write($this->getPrompt($groupName));
        $line = $this->historyShellManager->readInput();

        return $line;
    }

    /**
     * Renders a prompt.
     *
     * @param string $groupName The name of the group.
     *
     * @return string The prompt
     */
    protected function getPrompt($groupName)
    {
        // using the formatter here is required when using readline
        return $this->output->getFormatter()->format('Para <info>' . $groupName . '</info> > ');
    }

    /**
     * Returns historyShellManager.
     *
     * @return \Para\Service\HistoryShellManagerInterface
     */
    public function getHistoryShellManager()
    {
        return $this->historyShellManager;
    }
}
