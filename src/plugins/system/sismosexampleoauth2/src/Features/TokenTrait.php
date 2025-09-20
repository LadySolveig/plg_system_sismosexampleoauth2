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

namespace SiSmOS\Plugin\System\Sismosexampleoauth2\Features;

// no direct access
\defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Event\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\OAuth2\Client as OAuth2Client;

/**
 * Feature: Token Handling
 *
 * @since   __DEPLOY_VERSION__
 */
trait TokenTrait
{
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
        $context   = $event->getContext();
        $extension = $event->getItem();

        if ($context !== 'com_plugins.plugin' || $extension->element !== $this->_name) {
            return;
        }

        $newParams = new Registry($extension->get('params'));
        $tokenData = $newParams->get('token', null);

        if (
            $tokenData && (!isset($this->params) || ($this->params->get('public_key', '') !== $newParams->get('public_key', '')
            || $this->params->get('secret_key', '') !== $newParams->get('secret_key', '')))
        ) {
            $tokenData = ArrayHelper::fromObject($newParams->get('token', []));
            foreach ($tokenData as $key => &$data) {
                $data = "";
            }
            $newParams->token = ArrayHelper::toObject(['token' => $tokenData]);
            $extension->set('params', $newParams->toString());
        }
    }

    /**
     * Generate a token for OAuth2.
     * This method acts on table save, when a token doesn't already exist or a reset is required.
     *
     * @param   Model\SaveEvent  $event The onExtensionBeforeSave event.
     *
     * @return void
     *
     * @since __DEPLOY_VERSION__
     */
    public function onExtensionAfterSave(Model\SaveEvent $event): void
    {
        $context  = $event->getContext();
        $item     = $event->getItem();
        $data     = $event->getData();

        if ($context !== 'com_plugins.plugin' || $item->element !== $this->_name) {
            return;
        }

        if (\is_null($item)) {
            return;
        }

        $app = $this->getApp();

        //get gentoken value and check
        if ($app->getInput()->get('gentoken', null, 'int')) {
            $isRoot = $app->getIdentity()->authorise('core.admin');
            if ($isRoot) {
                if (
                    !\array_key_exists('public_key', $data['params']) || !$data['params']['public_key'] ||
                    !\array_key_exists('secret_key', $data['params']) || !$data['params']['secret_key']
                ) {
                    $app->enqueueMessage(Text::_('PLG_SYSTEM_SISMOSEXAMPLEOAUTH2_AUTH_MISSING_DATA_ERROR'), 'warning');
                    $this->log('Client-id and/or client-secret missing.', Log::ERROR);
                    return;
                }

                // @todo try/catch? or test
                $this->OAuth2Authenticate();

            } else {
                $app->enqueueMessage(Text::_('PLG_SYSTEM_SISMOSEXAMPLEOAUTH2_ERROR_ONLY_ADMIN_CAN_AUTHORIZE'), 'warning');
                $this->log('Only admins can authorise API connections.', Log::ERROR);
            }
        }
    }

    /**
     * OAuth2 Authentication routine.
     *
     * @since __DEPLOY_VERSION__
     * @throws Exception
     */
    protected function OAuth2Authenticate()
    {

        $baseUrl = $this->getBaseUrl();
        $app     = $this->getApplication();
        $params  = $this->getParams();

        if (!$baseUrl) {
            $this->log('Base URL missing.', Log::ERROR);
            throw new \Exception(Text::_('PLG_SYSTEM_SISMOSEXAMPLEOAUTH2_AUTH_MISSING_DATA_ERROR'));
            return;
        }

        $redirect = Uri::root() . 'index.php?option=com_ajax&plugin=' . $this->_name . '&format=raw';

        $options  = [
            'redirecturi'  => $redirect,
            'clientid'     => $params->get('public_key', 1),
            'clientsecret' => $params->get('secret_key', ''),
            'tokenurl'     => $baseUrl . '/login/oauth/access_token',
            'authurl'      => $baseUrl . '/login/oauth/authorize',
        ];

        $token = $params->get('token', '');
        $token = ($token) ? ArrayHelper::fromObject($token) : '';

        if ($token && \array_key_exists('refresh_token', $token) && $token['refresh_token']) {
            $options['userefresh'] = true;
        } elseif (!$app->getInput()->get('code', false, 'raw')) {
            $options['sendheaders'] = true;
        }

        $client = new OAuth2Client($options, null, $app->getInput(), $app);

        if (\array_key_exists('userefresh', $options) && $options['userefresh']) {
            $response = $client->refreshToken($token['refresh_token']);
        } else {
            $response = $client->authenticate();
        }

        if ($response instanceof UnexpectedResponseException) {
            Factory::getApplication()->enqueueMessage('PLG_SYSTEM_SISMOSEXAMPLEOAUTH2_AUTH_ERROR', 'error');
            return;
        }

        if ($response) {
            $this->saveToken($response);
        }
    }

    /**
     * Save the access token to the database.
     *
     * @param array|object $response The response from the token endpoint.
     *
     * @since __DEPLOY_VERSION__
     */
    private function saveToken(array|object $response)
    {
        $plugin = PluginHelper::getPlugin($this->_type, $this->_name);

        if (!isset($plugin->id)) {
            $this->log('No plugin id found to save the token.', Log::ERROR);
            return;
        }

        $accessTokenData = (object) $response;
        $accessTokenData->created = Factory::getDate()->toSql();
        $logTokenData    = clone $accessTokenData;

        // mask sensitive information for log
        $logTokenData->access_token  = '**(hidden)**';
        $logTokenData->refresh_token = '**(hidden)**';

        $this->getParams()->set('token', $accessTokenData);
        $this->getParams()->set('code', '');

        $params = json_encode($this->getParams(), JSON_UNESCAPED_SLASHES);
        $this->log('authorize::accessTokenData: ' . \var_export($logTokenData, true), Log::INFO);

        $db = $this->getDB();
        try {
            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = :params')
                ->where($db->quoteName('extension_id') . ' = :extid')
                ->bind(':params', $params, ParameterType::STRING)
                ->bind(':extid', $plugin->id, ParameterType::INTEGER);

            $db->setQuery($query);

            $db->execute();
        } catch (\Exception $e) {
            $this->log(Text::sprintf('Error while saving the oAuth token to db:\n %s', $e->getMessage() . ' -Response: ' . (\is_array($response) ? print_r($response, true) : $response)), Log::ERROR);
            $this->getApp()->enqueueMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Get the database connection.
     */
    private function getDB()
    {
        return $this instanceof DatabaseAwareTrait ? $this->getDatabase() : Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Get the application instance.
     */
    private function getApp()
    {
        return $this->app ?? Factory::getApplication();
    }

    /**
     * Get the parameters.
     */
    private function getParams()
    {
        return $this->params ?? new Registry();
    }

    /**
     * Get the baseUrl for OAuth2
     */
    private function getBaseUrl()
    {
        $baseUrl = $this->getParams()->get('base_url', '');
        return $baseUrl ? trim($baseUrl, " \t\n\r\0\x0B/") : '';
    }

    /**
     * Log helper function
     *
     * @return  string
     */
    abstract private function log($msg, $type);
}
