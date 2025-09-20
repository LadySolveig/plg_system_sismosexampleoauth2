<?php

/**
 * @package    SiSmOS.Plugin
 * @subpackage System.sismosexampleoauth2
 *
 * @author     Martina Scholz <support@simplysmart-it.de>
 *
 * @copyright  (C) 2025, SimplySmart-IT - Martina Scholz <https://simplysmart-it.de>
 * @license    GNU General Public License version 3 or later; see LICENSE
 * @link       https://simplysmart-it.de
 */

 \defined('_JEXEC') or die;

 use Joomla\CMS\Extension\PluginInterface;
 use Joomla\CMS\Factory;
 use Joomla\CMS\Plugin\PluginHelper;
 use Joomla\Database\DatabaseInterface;
 use Joomla\DI\Container;
 use Joomla\DI\ServiceProviderInterface;
 use Joomla\Event\DispatcherInterface;
 use SiSmOS\Plugin\System\Sismosexampleoauth2\Extension\Sismosexampleoauth2;

 return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin     = new Sismosexampleoauth2(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'sismosexampleoauth2')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }
        );
    }
};