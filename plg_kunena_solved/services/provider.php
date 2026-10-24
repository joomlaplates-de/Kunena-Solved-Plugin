<?php

/**
 * Kunena Plugin - Solved
 *
 * @package     Kunena.Plugins
 * @subpackage  Solved
 * @copyright   (C) 2026 Joomlaplates
 * @license     GNU/GPLv3 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomlaplates\Plugin\Kunena\Solved\Extension\Solved;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.1.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $config     = (array) PluginHelper::getPlugin('kunena', 'solved');

                // Joomla 5.4+ / 6.x take the config only; Joomla 5.0-5.3 require the dispatcher as first argument
                if (version_compare(JVERSION, '5.4', '>=')) {
                    $plugin = new Solved($config);
                    $plugin->traitSetDispatcher($dispatcher);
                } else {
                    $plugin = new Solved($dispatcher, $config);
                }

                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get('DatabaseDriver'));

                return $plugin;
            }
        );
    }
};
