<?php
/**
 * @file
 * Contains lrackwitz\Para\Service\YamlConfigurationManager.php.
 */

namespace lrackwitz\Para\Service;

use lrackwitz\Para\Exception\GroupNotFoundException;
use lrackwitz\Para\Exception\ProjectNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Class YamlConfigurationManager.
 *
 * @package lrackwitz\Para\Service
 */
class YamlConfigurationManager implements ConfigurationManagerInterface
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Dumps data into a yaml file.
     *
     * @var Dumper
     */
    private $dumper;

    /**
     * Reads and parses data from a yaml file.
     *
     * @var Parser
     */
    private $parser;

    /**
     * The path to the yaml file.
     *
     * @var string
     */
    private $yamlFile;

    /**
     * YamlConfigurationManager constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger The logger.
     * @param \Symfony\Component\Yaml\Dumper $dumper The yaml file dumper.
     * @param \Symfony\Component\Yaml\Parser $parser The yaml file parser.
     * @param string $yamlFile The path to the yaml file.
     */
    public function __construct(
        LoggerInterface $logger,
        Dumper $dumper,
        Parser $parser,
        $yamlFile
    ) {
        $this->logger = $logger;
        $this->dumper = $dumper;
        $this->parser = $parser;
        $this->yamlFile = $yamlFile;
    }

    /**
     * Adds a new group to the configuration.
     *
     * @param string $groupName The name of the group.
     *
     * @return bool True if the group has been added successfully, otherwise false.
     */
    public function addGroup($groupName)
    {
        // Is the group already existing.
        if ($this->existsGroup($groupName)) {
            $this->logger->warning('The group to add is already existing in the configuration.', [
                'groupName' => $groupName,
            ]);
            return false;
        }

        // Get the current configuration.
        $yaml = $this->readFile($this->yamlFile);

        $yaml[$groupName] = [];

        if (!$this->saveFileContent($this->yamlFile, $yaml)) {
            $this->logger->error('Failed to save the new group in the configuration file.', [
               'groupName' => $groupName,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Deletes an existing group and all its projects from the configuration.
     *
     * @param string $groupName The name of the group.
     *
     * @return bool True if the group has been deleted successfully, otherwise false.
     *
     * @throws \lrackwitz\Para\Exception\GroupNotFoundException
     */
    public function deleteGroup($groupName)
    {
        // Check if the group exists.
        if ($this->existsGroup($groupName)) {
            // Get the current configuration.
            $yaml = $this->readFile($this->yamlFile);

            // Remove the group.
            unset($yaml[$groupName]);

            // Save the file.
            if (!$this->saveFileContent($this->yamlFile, $yaml)) {
                $this->logger->error('Failed to save the configuration file atfer removing the group.', [
                    'groupName' => $groupName,
                    'yamlFile' => $this->yamlFile,
                ]);
                return false;
            }
        } else {
            $this->logger->warning('The group to delete is not existing in the configuration.', [
                'groupName' => $groupName,
                'yamlFile' => $this->yamlFile,
            ]);
            throw new GroupNotFoundException($groupName);
        }

        return true;
    }

    /**
     * Changes the name of an existing group.
     *
     * @param string $savedGroupName The name of the group to change.
     * @param string $newGroupName The new name of the group.
     */
    public function editGroupName($savedGroupName, $newGroupName)
    {
        // TODO: Implement editGroupName() method.
    }

    /**
     * Adds a new project to the configuration.
     *
     * If the group name is specified and the group does not exist,
     * the group will be created before. Finally the project will be added
     * as a child of this group.
     *
     * @param string $projectName The name of the project.
     * @param string $path The path where to find the project.
     * @param string $groupName (Optional) The name of the group. Defaults to 'default'.
     *
     * @return bool True if the project has been added successfully, otherwise false.
     */
    public function addProject($projectName, $path, $groupName = 'default')
    {
        // Is the project already existing.
        if ($this->existsProject($projectName)) {
            $this->logger->warning('The project to add is already existing in the configuration.', [
                'projectName' => $projectName,
            ]);
            return false;
        }

        // Add the group.
        $this->addGroup($groupName);

        // Get the current configuration.
        $yaml = $this->readFile($this->yamlFile);

        $yaml[$groupName][$projectName] = $path;

        if (!$this->saveFileContent($this->yamlFile, $yaml)) {
            $this->logger->error('Failed to save the new project in the configuration file.', [
                'projectName' => $projectName,
                'path' => $path,
                'groupName' => $groupName,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Deletes an existing project.
     *
     * @param string $projectName The name of the project.
     *
     * @return bool True if the project has been deleted successfully, otherwise false.
     *
     * @throws \lrackwitz\Para\Exception\ProjectNotFoundException
     */
    public function deleteProject($projectName)
    {
        // Check if the project exists.
        if ($group = $this->existsProject($projectName)) {
            // Get the current configuration.
            $yaml = $this->readFile($this->yamlFile);

            // Remove the project.
            unset($yaml[$group][$projectName]);

            // Save the file.
            if (!$this->saveFileContent($this->yamlFile, $yaml)) {
                $this->logger->error('Failed to save the configuration file atfer removing the project.', [
                    'projectName' => $projectName,
                    'yamlFile' => $this->yamlFile,
                ]);
                return false;
            }
        } else {
            $this->logger->warning('The project to delete is not existing in the configuration.', [
                'projectName' => $projectName,
                'yamlFile' => $this->yamlFile,
            ]);
            throw new ProjectNotFoundException($projectName);
        }

        return true;
    }

    /**
     * Changes the name of an existing project.
     *
     * @param string $projectName The name of the project to change.
     * @param string $newProjectName The new name of the project.
     */
    public function editProjectName($projectName, $newProjectName)
    {
        // TODO: Implement editProjectName() method.
    }

    /**
     * Changes the path of the project.
     *
     * @param string $projectName The name of the project.
     * @param string $path The new path of the project.
     */
    public function editProjectPath($projectName, $path)
    {
        // TODO: Implement editProjectPath() method.
    }

    /**
     * Reads all groups from the configuration.
     *
     * @return string[] An array with groups.
     */
    public function readGroups()
    {
        // TODO: Implement readGroups() method.
    }

    /**
     * Reads the information of an existing group from the configuration.
     *
     * @param string $groupName The name of the group.
     *
     * @return \string[] An array with information of the group.
     *
     * @throws \lrackwitz\Para\Exception\GroupNotFoundException If the group is not existing.
     */
    public function readGroup($groupName)
    {
        // Check if the group exists.
        if ($this->existsGroup($groupName)) {
            // Get the current configuration.
            $yaml = $this->readFile($this->yamlFile);

            return $yaml[$groupName];
        } else {
            $this->logger->warning('The group to read is not existing in the configuration.', [
                'groupName' => $groupName,
                'yamlFile' => $this->yamlFile,
            ]);
            throw new GroupNotFoundException($groupName);
        }
    }

    /**
     * Reads the information of an existing project from the configuration.
     *
     * @param string $projectName The name of the project.
     *
     * @return string[] An array with information of the project.
     */
    public function readProject($projectName)
    {
        // TODO: Implement readProject() method.
    }

    /**
     * Returns true if the project exists in the configuration otherwise false.
     *
     * @param string $projectName The name of the project.
     *
     * @return string|false The group where the project is stored or false.
     */
    private function existsProject($projectName)
    {
        $yaml = $this->readFile($this->yamlFile);

        foreach ($yaml as $groupName => $group) {
            if (isset($group[$projectName])) {
                return $groupName;
            }
        }

        return false;
    }

    /**
     * Checks if the group already exists in the configuration file.
     *
     * @param string $groupName The group name.
     *
     * @return bool Return true if the group exists, otherwise false.
     */
    private function existsGroup($groupName)
    {
        $yaml = $this->readFile($this->yamlFile);

        return array_key_exists($groupName, $yaml);
    }



    /**
     * Reads the yaml file.
     *
     * @param string $yamlFile The yaml file to read.
     *
     * @return string[] An array with the parsed file content.
     */
    private function readFile($yamlFile)
    {
        // Make sure the file exists.
        $this->createFile($yamlFile);

        $value = null;
        try {
            $value = $this->parser->parse(file_get_contents($yamlFile));
        } catch (ParseException $e) {
            $this->logger->error('Failed to read configuration file.', ['exception' => $e]);
            throw $e;
        }

        if (!$value) {
            $value = [];
        }

        return $value;
    }

    /**
     * Saves the yaml data to the configuration file.
     *
     * @param string $yamlFile The path of the yaml file.
     * @param array $content The configuration content.
     *
     * @return string The representation of the saved content.
     */
    private function saveFileContent($yamlFile, array $content)
    {
        // Make sure the file exists.
        $this->createFile($yamlFile);

        return file_put_contents($yamlFile, $this->dumper->dump($content, 3));
    }

    /**
     * If the yaml file does not exist the file will be created.
     *
     * @param string $yamlFile The path of the yaml file.
     */
    private function createFile($yamlFile)
    {
        if (!file_exists($yamlFile)) {
            if (!touch($yamlFile)) {
                $this->logger->error('Could not create configuration file.', ['file' => $yamlFile]);
                throw new FileNotFoundException('The configuration file "' . $yamlFile . ' could not be created.', 1);
            }
        }
    }

    /**
     * Checks if a group exists in the configuration.
     *
     * @param string $groupName The name of the group
     *
     * @return bool Returns true if existing, otherwise false.
     */
    public function hasGroup($groupName)
    {
        return $this->existsGroup($groupName);
    }
}