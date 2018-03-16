<?php

namespace Para\Tests\Unit\Plugin;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Locker;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryManager;
use Para\Factory\CompositeRepositoryFactoryInterface;
use Para\Factory\PluginFactoryInterface;
use Para\Factory\ProcessFactoryInterface;
use Para\Para;
use Para\Plugin\PluginInterface;
use Para\Plugin\PluginManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Process\Process;

/**
 * Class PluginManagerTest
 *
 * @package Para\Tests\Unit\Plugin
 */
class PluginManagerTest extends TestCase
{
    /**
     * The plugin manager to test.
     *
     * @var \Para\Plugin\PluginManagerInterface
     */
    private $pluginManager;

    /**
     * The composite repository factory mock object.
     *
     * @var \Para\Factory\CompositeRepositoryFactoryInterface
     */
    private $repositoryFactory;

    /**
     * The composer factory mock object.
     *
     * @var \Composer\Factory
     */
    private $composerFactory;

    /**
     * The plugin factory mock object.
     *
     * @var \Para\Factory\PluginFactoryInterface
     */
    private $pluginFactory;

    /**
     * The process factory mock object.
     *
     * @var \Para\Factory\ProcessFactoryInterface
     */
    private $processFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->repositoryFactory = $this->prophesize(CompositeRepositoryFactoryInterface::class);
        $this->composerFactory = $this->prophesize(Factory::class);
        $this->pluginFactory = $this->prophesize(PluginFactoryInterface::class);
        $this->processFactory = $this->prophesize(ProcessFactoryInterface::class);

        $this->pluginManager = new PluginManager(
            $this->repositoryFactory->reveal(),
            $this->composerFactory->reveal(),
            $this->pluginFactory->reveal(),
            $this->processFactory->reveal(),
            'the/path/to/the/root/directory/of/para'
        );
    }

    /**
     * Tests that the fetchPluginsAvailable() method returns an array of available plugins.
     */
    public function testTheMethodFetchPluginsAvailableReturnsAnArrayOfAvailablePlugins()
    {
        $composer = $this->prophesize(Composer::class);

        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->shouldBeCalled();
        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->willReturn($composer->reveal());

        $repositoryManager = $this->prophesize(RepositoryManager::class);
        $repositoryManager->getRepositories()->shouldBeCalled();
        $repositoryManager
            ->getRepositories()
            ->willReturn([]);

        $composer->getRepositoryManager()->shouldBeCalled();
        $composer->getRepositoryManager()->willReturn($repositoryManager->reveal());

        $compositeRepository = $this->prophesize(CompositeRepository::class);
        $compositeRepository
            ->search(Argument::type('string'), ComposerRepository::SEARCH_FULLTEXT, 'para-plugin')
            ->shouldBeCalled();
        $compositeRepository
            ->search(Argument::type('string'), ComposerRepository::SEARCH_FULLTEXT, 'para-plugin')
            ->willReturn([
                ['name' => 'para-alias', 'description' => 'the description'],
                ['name' => 'para-sync', 'description' => 'the description'],
            ]);

        $this->repositoryFactory->getRepository(Argument::type('array'))->shouldBeCalled();
        $this->repositoryFactory
            ->getRepository(Argument::type('array'))
            ->willReturn($compositeRepository->reveal());

        $aliasPlugin = $this->prophesize(PluginInterface::class);

        $this->pluginFactory->getPlugin(Argument::type('string'))->shouldBeCalledTimes(2);
        $this->pluginFactory
            ->getPlugin('para-alias')
            ->willReturn($aliasPlugin->reveal());

        $syncPlugin = $this->prophesize(PluginInterface::class);

        $this->pluginFactory
            ->getPlugin('para-sync')
            ->willReturn($syncPlugin->reveal());

        $plugins = $this->pluginManager->fetchPluginsAvailable();

        $this->assertTrue(is_array($plugins), $plugins);
        $this->assertTrue($plugins['para-alias'] instanceof PluginInterface);
        $this->assertTrue($plugins['para-sync'] instanceof PluginInterface);
    }

    /**
     * Tests that a plugin installs a plugin successfully
     */
    public function testInstallsAPluginSuccessfully()
    {
        $pluginName = 'lrackwitz/para-alias';
        $pluginVersion = 'dev-master';

        $commandline = 'composer require lrackwitz/para-alias dev-master';
        $cwd = 'the/path/to/the/root/directory/of/para';

        $locker = $this->prophesize(Locker::class);
        $locker->getLockData()->shouldBeCalled();
        $locker->getLockData()->willReturn([
            'packages' => [],
        ]);

        $composer = $this->prophesize(Composer::class);
        $composer->getLocker()->shouldBeCalled();
        $composer->getLocker()->willReturn($locker->reveal());

        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->shouldBeCalled();
        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->willReturn($composer->reveal());

        $process = $this->prophesize(Process::class);
        $process->run()->shouldBeCalled();
        $process->isSuccessful()->shouldBeCalled();
        $process->isSuccessful()->willReturn(true);
        $process->getOutput()->shouldBeCalled();
        $process->getOutput()->willReturn('something');

        $this->processFactory
            ->getProcess($commandline, $cwd)
            ->shouldBeCalled();
        $this->processFactory
            ->getProcess($commandline, $cwd)
            ->willReturn($process->reveal());

        $this->pluginManager->installPlugin($pluginName, $pluginVersion);
    }

    /**
     * Tests that installing a plugin fails.
     *
     * @expectedException \Para\Exception\PluginNotFoundException
     */
    public function testInstallingAPluginFails()
    {
        $pluginName = 'lrackwitz/para-alias';
        $pluginVersion = 'dev-master';

        $commandline = 'composer require lrackwitz/para-alias dev-master';
        $cwd = 'the/path/to/the/root/directory/of/para';

        $locker = $this->prophesize(Locker::class);
        $locker->getLockData()->shouldBeCalled();
        $locker->getLockData()->willReturn([
            'packages' => [],
        ]);

        $composer = $this->prophesize(Composer::class);
        $composer->getLocker()->shouldBeCalled();
        $composer->getLocker()->willReturn($locker->reveal());

        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->shouldBeCalled();
        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->willReturn($composer->reveal());

        $process = $this->prophesize(Process::class);
        $process->run()->shouldBeCalled();
        $process->isSuccessful()->shouldBeCalled();
        $process->isSuccessful()->willReturn(false);

        $this->processFactory
            ->getProcess($commandline, $cwd)
            ->shouldBeCalled();
        $this->processFactory
            ->getProcess($commandline, $cwd)
            ->willReturn($process->reveal());

        $this->pluginManager->installPlugin($pluginName, $pluginVersion);
    }

    /**
     * Tests that the isInstalled() method returns true when the plugin is already installed.
     */
    public function testTheIsInstalledMethodReturnsTrueWhenThePluginIsAlreadyInstalled()
    {
        $pluginName = 'lrackwitz/para-alias';

        $locker = $this->prophesize(Locker::class);
        $locker->getLockData()->shouldBeCalled();
        $locker->getLockData()->willReturn([
            'packages' => [
                [
                    'name' => 'lrackwitz/para-alias',
                ],
            ],
        ]);

        $composer = $this->prophesize(Composer::class);
        $composer->getLocker()->shouldBeCalled();
        $composer->getLocker()->willReturn($locker->reveal());

        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->shouldBeCalled();
        $this->composerFactory
            ->createComposer(Argument::any(), Argument::type('string'), false, Argument::type('string'), true)
            ->willReturn($composer->reveal());

        $result = $this->pluginManager->isInstalled($pluginName);

        $this->assertTrue($result);
    }
}