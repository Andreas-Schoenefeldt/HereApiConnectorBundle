<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 30/03/17
 * Time: 16:10
 */

namespace Schoenef\HereApiConnectorBundle\DependencyInjection;


use Schoenef\HereApiConnector\Service\HereApiConnector;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 * this is testing the configuration in the following manner:
 * html2pdf:
 *   provider: defualt pdfrocket
 *   timeout: default 20
 *   apikey: required
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {
    const CONFIG_NAMESPACE = 'here_api_connector';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        // the root must be the name of the bundle: http://stackoverflow.com/a/35505189/2776727
        $rootNode = $treeBuilder->root(self::CONFIG_NAMESPACE);

        $rootNode
            ->children()
            ->scalarNode(HereApiConnector::KEY_APP_ID)->end()
            ->scalarNode(HereApiConnector::KEY_APP_CODE)->end()
            ->scalarNode(HereApiConnector::KEY_API_KEY)->end()
            ->scalarNode(HereApiConnector::KEY_COUNTRY)->end()
            ->enumNode(HereApiConnector::KEY_LANG)->values(['en', 'de', 'fr', 'es', 'ru'])->defaultValue('en')->end()
            ->integerNode(HereApiConnector::KEY_TIMEOUT)->defaultValue(20)->end()
            ->end();
        return $treeBuilder;
    }
}