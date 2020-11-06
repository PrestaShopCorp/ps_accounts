<?php

namespace PrestaShop\Module\PsAccounts\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('ps_accounts')
            ->children()
                ->arrayNode('firebase')
                    ->children()
                        ->scalarNode('api_key')
                            ->isRequired()
                        ->end()
                        ->scalarNode('auth_domain')
                            ->isRequired()
                        ->end()
                        ->scalarNode('project_id')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param array|null $files
     *
     * @return array
     *
     * @throws \Exception
     */
    public function process($files = null)
    {
//        if (! is_array($files)) {
//            $files = [ __DIR__ . '/../../config/' . getenv('APP_ENV') . '.yml' ];
//        }
//
//        return (new Processor())->processConfiguration($this, array_map(function ($file) {
//            return Yaml::parseFile($file);
//        }, $files));

        // Processor(Definition, files...): array
        // Env vars interpolation

        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config/'));
        $loader->load('config' . getenv('APP_ENV') . '.yml');

        return (new Processor())->processConfiguration($this, [
            $container->get('ps_accounts')
        ]);

    }
}
