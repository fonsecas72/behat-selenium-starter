<?php

namespace Fonsecas72\SeleniumStarterExtension\ServiceContainer;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Behat\Testwork\ServiceContainer\Extension;
use BeubiQA\Application\Selenium\Options\SeleniumStartOptions;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use BeubiQA\Application\Lib\ResponseWaitter;
use GuzzleHttp\Client;
use BeubiQA\Application\Selenium\SeleniumStarter;
use BeubiQA\Application\Selenium\SeleniumStopper;
use BeubiQA\Application\Selenium\SeleniumDownloader;
use BeubiQA\Application\Lib\LogWatcher;
use BeubiQA\Application\Selenium\SeleniumHandler;
use BeubiQA\Application\Selenium\Options\SeleniumStopOptions;
use BeubiQA\Application\Selenium\Options\SeleniumDownloaderOptions;

class SeleniumStarterExtension implements Extension
{

    public function initialize(ExtensionManager $extensionManager)
    {

    }

    public function load(ContainerBuilder $container, array $config)
    {
        $process = new Process('');
        $exeFinder = new ExecutableFinder();
        $httpClient = new Client();
        $waiter = new ResponseWaitter($httpClient);
        $seleniumStarterOptions = new SeleniumStartOptions();
        $seleniumStarterOptions->setSeleniumJarLocation($config['selenium_jar_folder'].'/selenium-server-standalone.jar');
        if ($config['xvfb']) {
            $seleniumStarterOptions->enabledXvfb();
        }
        $seleniumStarter = new SeleniumStarter($seleniumStarterOptions, $process, $waiter, $exeFinder);
        $seleniumStopperOptions = new SeleniumStopOptions();
        $seleniumStopper = new SeleniumStopper($seleniumStopperOptions, $waiter, $httpClient);
        $seleniumDownloaderOptions = new SeleniumDownloaderOptions();
        $seleniumDownloaderOptions->setSeleniumDestination($config['selenium_jar_folder']);
        $seleniumDownloaderOptions->setSeleniumVersion($config['selenium_version']);

        $seleniumDownloader = new SeleniumDownloader($seleniumDownloaderOptions, $httpClient);
        $seleniumLogWatcher = new LogWatcher();
        $seleniumHandler = new SeleniumHandler(
            $seleniumStarter, $seleniumStopper, $seleniumDownloader, $seleniumLogWatcher
        );

        $definition = new Definition('Fonsecas72\SeleniumStarterExtension\SeleniumStarterListener', [$seleniumHandler]);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, array('priority' => 0));
        $container->setDefinition('selenium_starter', $definition);
        $container->setParameter('selenium_starter.selenium_jar_folder', $config['selenium_jar_folder']);
        $container->setParameter('selenium_starter.xvfb', $config['xvfb']);
    }

    public function getConfigKey()
    {
        return 'selenium_starter';
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
                    ->scalarNode('selenium_jar_folder')
                    ->isRequired()
                    ->end()
                ->end()
                ->children()
                    ->booleanNode('xvfb')
                    ->end()
                ->end()
                ->children()
                    ->scalarNode('selenium_version')
                    ->end()
                ->end();
    }

    public function process(ContainerBuilder $container)
    {

    }
}
