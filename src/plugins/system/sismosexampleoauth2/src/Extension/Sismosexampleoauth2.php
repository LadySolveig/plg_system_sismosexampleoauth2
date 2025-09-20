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

namespace SiSmOS\Plugin\System\Sismosexampleoauth2\Extension;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use SiSmOS\Plugin\System\Sismosexampleoauth2\Features\TokenTrait;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Sismosexampleoauth2 System Plugin
 *
 * @since  __DEPLOY_VERSION__
 */
final class Sismosexampleoauth2 extends CMSPlugin implements SubscriberInterface
{
    // Use Traits to improve maintainability by separating functionality into focused units.
    use TokenTrait
    {
        TokenTrait::onExtensionBeforeSave as protected onExtensionBeforeSave_TokenTrait;
        TokenTrait::onExtensionAfterSave as protected onExtensionAfterSave_TokenTrait;
    }
    use DatabaseAwareTrait;

    /**
     * Application object
     *
     * @var    CMSApplication
     * @since  __DEPLOY_VERSION__
     */
    protected $app;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     * Note this is only available in Joomla 3.1 and higher.
     * If you want to support 3.0 series you must override the constructor
     *
     * @var    boolean
     * @since  __DEPLOY_VERSION__
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor.
     *
     * @param   DispatcherInterface       $dispatcher       The dispatcher
     * @param   array                     $config           An optional associative array of configuration settings
     *
     * @since   3.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        // Define the logger.
        Log::addLogger([
            'text_file' => 'plg_system_sismosexampleoauth2.php',
            'text_entry_format' => '{DATETIME}	{PRIORITY} {CLIENTIP}	{MESSAGE}',
        ], Log::ALL, [
            'plg_system_sismosexampleoauth2'
        ]);
    }
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     *
     * @see libraries/src/Event/CoreEventAware.php
     */
    public static function getSubscribedEvents(): array
    {
        $app = Factory::getApplication();

        $mapping  = [];

        // Only allowed in the backend
        if ($app->isClient('administrator')) {
            $mapping['onExtensionBeforeSave']     = 'onExtensionBeforeSave';
            $mapping['onExtensionAfterSave']      = 'onExtensionAfterSave';
        } else {
            $mapping['onAjaxSismosexampleoauth2'] = 'onAjaxSismosexampleoauth2';
        }

        return $mapping;
    }

    /**
     * Ajax callback for OAuth2.
     *
     * @param   AjaxEvent  $event  The event object
     *
     * @return  void
     *
     * @since __DEPLOY_VERSION__
     */
    public function onAjaxSismosexampleoauth2(AjaxEvent $event)
    {
        $app = $event->getApplication();

        $code = $app->getInput()->get('code', false, 'raw');

        if (!$code) {
            return;
        }

        $plugin = PluginHelper::getPlugin('system', 'sismosexampleoauth2');

        // Perform OAuth2 authentication and retrieve and store an access token (handled by TokenTrait)
        $this->OAuth2Authenticate();

        $url = Uri::root() . 'administrator/index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->id;

        $this->app->redirect($url, (int) 303);
    }

    /**
     * Clear all Data from token when keys change.
     * This method acts on table save, checks old data and clears the token data if the keys have changed.
     *
     * @param   Model\SaveEvent  $event The onExtensionBeforeSave event.
     *
     * @return void
     *
     * @since __DEPLOY_VERSION__
     */
    public function onExtensionBeforeSave(Model\SaveEvent $event): void
    {
        // Call the trait method in PrepareContentTrait
        $this->onExtensionBeforeSave_TokenTrait($event);
    }

    /**
     * Generate a token for Mautic oAuth.
     * This method acts on table save, when a token doesn't already exist or a reset is required.
     *
     * @param   Model\SaveEvent  $event The onExtensionBeforeSave event.
     *
     * @return void
     *
     * @since __DEPLOY_VERSION_
     */
    public function onExtensionAfterSave(Model\SaveEvent $event): void
    {
        // Call the method in TokenTrait
        $this->onExtensionAfterSave_TokenTrait($event);
    }

    /**
     * Log helper function
     *
     * @return  string
     */
    private function log($msg, $type)
    {
        if ($this->params->get('log_on', 1)) {
            Log::add($msg, $type, 'plg_system_sismosexampleoauth2');
        }
    }

}
