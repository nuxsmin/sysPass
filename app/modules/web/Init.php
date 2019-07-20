<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use SP\Bootstrap;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\UUIDCookie;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\Http\Address;
use SP\Plugin\PluginManager;
use SP\Repositories\NoSuchItemException;
use SP\Services\Crypt\SecureSessionService;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Services\Upgrade\UpgradeAppService;
use SP\Services\Upgrade\UpgradeDatabaseService;
use SP\Services\Upgrade\UpgradeUtil;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\File\FileException;
use SP\Util\HttpUtil;

/**
 * Class Init
 *
 * @property  itemPresetService
 * @package SP\Modules\Web
 */
final class Init extends ModuleBase
{
    /**
     * List of controllers that don't need to perform fully initialization
     * like: install/database checks, session/event handlers initialization
     */
    const PARTIAL_INIT = [
        'resource',
        'install',
        'bootstrap',
        'status',
        'upgrade',
        'error',
        'task'
    ];
    /**
     * List of controllers that don't need to update the user's session activity
     */
    const NO_SESSION_ACTIVITY = ['items', 'login'];

    /**
     * @var SessionContext
     */
    private $context;
    /**
     * @var ThemeInterface
     */
    private $theme;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var SecureSessionService
     */
    private $secureSessionService;
    /**
     * @var PluginManager
     */
    private $pluginManager;
    /**
     * @var ItemPresetService
     */
    private $itemPresetService;
    /**
     * @var bool
     */
    private $isIndex = false;

    /**
     * Init constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->context = $container->get(ContextInterface::class);
        $this->theme = $container->get(ThemeInterface::class);
        $this->language = $container->get(Language::class);
        $this->secureSessionService = $container->get(SecureSessionService::class);
        $this->pluginManager = $container->get(PluginManager::class);
        $this->itemPresetService = $container->get(ItemPresetService::class);
    }

    /**
     * Initialize Web App
     *
     * @param string $controller
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function initialize($controller)
    {
        logger(__METHOD__);

        $this->isIndex = $controller === 'index';

        // Iniciar la sesión de PHP
        $this->initSession($this->configData->isEncryptSession());

        $isReload = $this->request->checkReload();

        // Volver a cargar la configuración si se recarga la página
        if ($isReload) {
            logger('Browser reload');

            $this->context->setAppStatus(SessionContext::APP_STATUS_RELOADED);

            // Cargar la configuración
            $this->config->loadConfig(true);
        }

        // Setup language
        $this->language->setLanguage($isReload);

        // Setup theme
        $this->theme->initTheme($isReload);

        // Comprobar si es necesario cambiar a HTTPS
        HttpUtil::checkHttps($this->configData, $this->request);

        if (in_array($controller, self::PARTIAL_INIT, true) === false) {
            $databaseUtil = $this->container->get(DatabaseUtil::class);

            // Checks if sysPass is installed
            if (!$this->checkInstalled()) {
                logger('Not installed', 'ERROR');

                $this->router->response()
                    ->redirect('index.php?r=install/index')
                    ->send();

                return;
            }

            // Checks if the database is set up
            if (!$databaseUtil->checkDatabaseConnection()) {
                logger('Database connection error', 'ERROR');

                $this->router->response()
                    ->redirect('index.php?r=error/databaseConnection')
                    ->send();

                return;
            }

            // Checks if maintenance mode is turned on
            if ($this->checkMaintenanceMode($this->context)) {
                logger('Maintenance mode', 'INFO');

                $this->router->response()
                    ->redirect('index.php?r=error/maintenanceError')
                    ->send();

                return;
            }

            // Checks if upgrade is needed
            if ($this->checkUpgrade()) {
                logger('Upgrade needed', 'ERROR');

                $this->config->generateUpgradeKey();

                $this->router->response()
                    ->redirect('index.php?r=upgrade/index')
                    ->send();

                return;
            }

            // Checks if the database is set up
            if (!$databaseUtil->checkDatabaseTables($this->configData->getDbName())) {
                logger('Database checking error', 'ERROR');

                $this->router->response()
                    ->redirect('index.php?r=error/databaseError')
                    ->send();

                return;
            }

            // Initialize event handlers
            $this->initEventHandlers();

            if (!in_array($controller, self::NO_SESSION_ACTIVITY)) {
                // Initialize user session context
                $this->initUserSession();
            }

            // Load plugins
            $this->pluginManager->loadPlugins();

            if ($this->context->isLoggedIn()
                && $this->context->getAppStatus() === SessionContext::APP_STATUS_RELOADED
            ) {
                logger('Reload user profile');
                // Recargar los permisos del perfil de usuario
                $this->context->setUserProfile(
                    $this->container->get(UserProfileService::class)
                        ->getById($this->context->getUserData()
                            ->getUserProfileId())->getProfile());
            }

            return;
        }

        // Do not keep the PHP's session opened
        SessionContext::close();
    }

    /**
     * Iniciar la sesión PHP
     *
     * @param bool $encrypt Encriptar la sesión de PHP
     *
     * @throws Exception
     */
    private function initSession($encrypt = false)
    {
        if ($encrypt === true
            && Bootstrap::$checkPhpVersion
            && ($key = $this->secureSessionService->getKey(UUIDCookie::factory($this->request))) !== false) {
            session_set_save_handler(new CryptSessionHandler($key), true);
        }


        try {
            $this->context->initialize();
        } catch (Exception $e) {
            $this->router->response()->header('HTTP/1.1', '500 Internal Server Error');

            throw $e;
        }
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     */
    private function checkInstalled()
    {
        return $this->configData->isInstalled()
            && $this->router->request()->param('r') !== 'install/index';
    }

    /**
     * Comprobar si es necesario actualizar componentes
     *
     * @throws FileException
     */
    private function checkUpgrade()
    {
        UpgradeUtil::fixAppUpgrade($this->configData, $this->config);

        return $this->configData->getUpgradeKey()
            || (UpgradeDatabaseService::needsUpgrade($this->configData->getDatabaseVersion()) ||
                UpgradeAppService::needsUpgrade($this->configData->getAppVersion()));
    }

    /**
     * Inicializar la sesión de usuario
     *
     */
    private function initUserSession()
    {
        $lastActivity = $this->context->getLastActivity();
        $inMaintenance = $this->configData->isMaintenance();

        // Session timeout
        if ($lastActivity > 0
            && !$inMaintenance
            && time() > ($lastActivity + $this->getSessionLifeTime())
        ) {
            if ($this->router->request()->cookies()->get(session_name()) !== null) {
                $this->router->response()->cookie(session_name(), '', time() - 42000);
            }

            SessionContext::restart();
        } else {
            $sidStartTime = $this->context->getSidStartTime();

            // Regenerate session's ID frequently to avoid fixation
            if ($sidStartTime === 0) {
                // Try to set PHP's session lifetime
                @ini_set('session.gc_maxlifetime', $this->getSessionLifeTime());
            } else if (!$inMaintenance
                && time() > ($sidStartTime + SessionContext::MAX_SID_TIME)
                && $this->context->isLoggedIn()
            ) {
                try {
                    CryptSession::reKey($this->context);
                } catch (CryptoException $e) {
                    logger($e->getMessage());

                    SessionContext::restart();
                    return;
                }
            }

            $this->context->setLastActivity(time());
        }
    }

    /**
     * Obtener el timeout de sesión desde la configuración.
     *
     * @return int con el tiempo en segundos
     */
    private function getSessionLifeTime()
    {
        $timeout = $this->context->getSessionTimeout();

        try {
            if ($this->isIndex || $timeout === null) {
                $userTimeout = $this->getSessionTimeoutForUser($timeout) ?: $this->configData->getSessionTimeout();

                logger('Session timeout: ' . $userTimeout);

                return $this->context->setSessionTimeout($userTimeout);
            }
        } catch (Exception $e) {
            processException($e);
        }

        return $timeout;
    }

    /**
     * @param int $default
     *
     * @return int
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    private function getSessionTimeoutForUser(int $default = null)
    {
        if ($this->context->isLoggedIn()) {
            $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT);

            if ($itemPreset !== null) {
                $sessionTimeout = $itemPreset->hydrate(SessionTimeout::class);

                if (Address::check($this->request->getClientAddress(), $sessionTimeout->getAddress(), $sessionTimeout->getMask())) {
                    return $sessionTimeout->getTimeout();
                }
            }
        }

        return $default;
    }
}