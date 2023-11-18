<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use Klein\Klein;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Bootstrap\BootstrapWeb;
use SP\Core\Context\ContextBase;
use SP\Core\Context\SessionContext;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Crypt\Csrf;
use SP\Core\Crypt\CsrfInterface;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\UuidCookie;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\HttpModuleBase;
use SP\Core\Language;
use SP\Core\LanguageInterface;
use SP\Core\ProvidersHelper;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\ItemPreset\SessionTimeout;
use SP\Domain\Crypt\Ports\SecureSessionServiceInterface;
use SP\Domain\Crypt\Services\SecureSessionService;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Services\ItemPresetService;
use SP\Domain\Upgrade\Services\UpgradeAppService;
use SP\Domain\Upgrade\Services\UpgradeDatabaseService;
use SP\Domain\Upgrade\Services\UpgradeUtil;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Services\UserProfileService;
use SP\Http\Address;
use SP\Http\RequestInterface;
use SP\Http\Uri;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\File\FileException;
use SP\Modules\Web\Controllers\Bootstrap\GetEnvironmentController;
use SP\Modules\Web\Controllers\Error\DatabaseConnectionController;
use SP\Modules\Web\Controllers\Error\DatabaseErrorController;
use SP\Modules\Web\Controllers\Error\IndexController as ErrorIndexController;
use SP\Modules\Web\Controllers\Install\InstallController;
use SP\Modules\Web\Controllers\Items\AccountsUserController;
use SP\Modules\Web\Controllers\Items\CategoriesController;
use SP\Modules\Web\Controllers\Items\ClientsController;
use SP\Modules\Web\Controllers\Items\NotificationsController;
use SP\Modules\Web\Controllers\Items\TagsController;
use SP\Modules\Web\Controllers\Login\LoginController;
use SP\Modules\Web\Controllers\Resource\CssController;
use SP\Modules\Web\Controllers\Resource\JsController;
use SP\Modules\Web\Controllers\Status\CheckNotices;
use SP\Modules\Web\Controllers\Status\StatusController;
use SP\Modules\Web\Controllers\Task\TrackStatusController;
use SP\Modules\Web\Controllers\Upgrade\IndexController as UpgradeIndexController;
use SP\Modules\Web\Controllers\Upgrade\UpgradeController;
use SP\Plugin\PluginManager;
use SP\Util\HttpUtil;

/**
 * Class Init
 */
final class Init extends HttpModuleBase
{
    /**
     * List of controllers that don't need to perform fully initialization
     * like: install/database checks, session/event handlers initialization
     */
    private const PARTIAL_INIT = [
        CssController::class,
        JsController::class,
        InstallController::class,
        GetEnvironmentController::class,
        CheckNotices::class,
        StatusController::class,
        UpgradeIndexController::class,
        UpgradeController::class,
        DatabaseConnectionController::class,
        DatabaseErrorController::class,
        ErrorIndexController::class,
        TrackStatusController::class,
    ];
    /**
     * List of controllers that don't need to update the user's session activity
     */
    private const NO_SESSION_ACTIVITY = [
        AccountsUserController::class,
        CategoriesController::class,
        ClientsController::class,
        NotificationsController::class,
        TagsController::class,
        LoginController::class,
    ];
    /**
     * List of controllers that needs to keep the session opened
     */
    private const NO_SESSION_CLOSE = [LoginController::class];
    /**
     * Routes
     */
    public const  ROUTE_INSTALL                   = 'install';
    public const  ROUTE_ERROR_DATABASE_CONNECTION = 'error/databaseConnection';
    public const  ROUTE_ERROR_MAINTENANCE         = 'error/maintenanceError';
    public const  ROUTE_ERROR_DATABASE            = 'error/databaseError';
    public const  ROUTE_UPGRADE                   = 'upgrade';


    private Csrf           $csrf;
    private ThemeInterface $theme;
    private Language             $language;
    private SecureSessionService $secureSessionService;
    private PluginManager        $pluginManager;
    private ItemPresetService    $itemPresetService;
    private DatabaseUtil         $databaseUtil;
    private UserProfileService   $userProfileService;
    private bool                 $isIndex = false;

    public function __construct(
        Application                   $application,
        ProvidersHelper               $providersHelper,
        RequestInterface              $request,
        Klein                         $router,
        CsrfInterface                 $csrf,
        ThemeInterface                $theme,
        LanguageInterface             $language,
        SecureSessionServiceInterface $secureSessionService,
        PluginManager                 $pluginManager,
        ItemPresetService             $itemPresetService,
        DatabaseUtil                  $databaseUtil,
        UserProfileServiceInterface   $userProfileService
    ) {
        parent::__construct(
            $application,
            $providersHelper,
            $request,
            $router
        );

        $this->csrf = $csrf;
        $this->theme = $theme;
        $this->language = $language;
        $this->secureSessionService = $secureSessionService;
        $this->pluginManager = $pluginManager;
        $this->itemPresetService = $itemPresetService;
        $this->databaseUtil = $databaseUtil;
        $this->userProfileService = $userProfileService;
    }

    /**
     * Initialize Web App
     *
     * @param  string  $controller
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InitializationException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Infrastructure\File\FileException
     * @throws \Exception
     */
    public function initialize(string $controller): void
    {
        logger(__METHOD__);

        $this->isIndex = $controller === 'index';

        // Iniciar la sesión de PHP
        $this->initSession($this->configData->isEncryptSession());

        $isReload = $this->request->checkReload();

        // Volver a cargar la configuración si se recarga la página
        if ($isReload) {
            logger('Browser reload');

            $this->context->setAppStatus(ContextBase::APP_STATUS_RELOADED);

            // Cargar la configuración
            $this->config->loadConfig(true);
        }

        // Setup language
        $this->language->setLanguage($isReload);

        // Setup theme
        $this->theme->initTheme($isReload);

        // Comprobar si es necesario cambiar a HTTPS
        HttpUtil::checkHttps($this->configData, $this->request);

        $partialInit = in_array($controller, self::PARTIAL_INIT, true);

        // Initialize event handlers
        $this->initEventHandlers($partialInit);

        if ($partialInit === false) {
            // Checks if sysPass is installed
            if (!$this->checkInstalled()) {
                logger('Not installed', 'ERROR');

                $this->router->response()->redirect(self::getUriFor(self::ROUTE_INSTALL))->send();

                throw new InitializationException('Not installed');
            }

            // Checks if the database is set up
            if (!$this->databaseUtil->checkDatabaseConnection()) {
                logger('Database connection error', 'ERROR');

                $this->router->response()->redirect(self::getUriFor(self::ROUTE_ERROR_DATABASE_CONNECTION))->send();

                throw new InitializationException('Database connection error');
            }

            // Checks if maintenance mode is turned on
            if ($this->checkMaintenanceMode()) {
                logger('Maintenance mode', 'INFO');

                $this->router->response()->redirect(self::getUriFor(self::ROUTE_ERROR_MAINTENANCE))->send();

                throw new InitializationException('Maintenance mode');
            }

            // Checks if upgrade is needed
            if ($this->checkUpgrade()) {
                logger('Upgrade needed', 'ERROR');

                $this->config->generateUpgradeKey();

                $this->router->response()->redirect(self::getUriFor(self::ROUTE_UPGRADE))->send();

                throw new InitializationException('Upgrade needed');
            }

            // Checks if the database is set up
            if (!$this->databaseUtil->checkDatabaseTables($this->configData->getDbName())) {
                logger('Database checking error', 'ERROR');

                $this->router->response()->redirect(self::getUriFor(self::ROUTE_ERROR_DATABASE))->send();

                throw new InitializationException('Database checking error');
            }

            if (!in_array($controller, self::NO_SESSION_ACTIVITY)) {
                // Initialize user session context
                $this->initUserSession();
            }

            // Load plugins
            $this->pluginManager->loadPlugins();

            if ($this->context->isLoggedIn()
                && $this->context->getAppStatus() === ContextBase::APP_STATUS_RELOADED
            ) {
                logger('Reload user profile');

                // Recargar los permisos del perfil de usuario
                $this->context->setUserProfile(
                    $this->userProfileService->getById($this->context->getUserData()->getUserProfileId())->getProfile()
                );
            }

            if (!$this->csrf->check()) {
                throw new InitializationException('Invalid request token');
            }

            // Initialize CSRF
            $this->csrf->initialize();
        }

        // Do not keep the PHP's session opened
        if (!in_array($controller, self::NO_SESSION_CLOSE, true)) {
            SessionContext::close();
        }
    }

    /**
     * Iniciar la sesión PHP
     *
     * @throws Exception
     */
    private function initSession(bool $encrypt = false): void
    {
        if ($encrypt === true
            && BootstrapBase::$checkPhpVersion
            && ($key = $this->secureSessionService->getKey()) !== false) {
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
    private function checkInstalled(): bool
    {
        return $this->configData->isInstalled()
               && $this->router->request()->param('r') !== 'install/index';
    }

    /**
     * Comprobar si es necesario actualizar componentes
     *
     * @throws FileException
     */
    private function checkUpgrade(): bool
    {
        UpgradeUtil::fixAppUpgrade($this->configData, $this->config);

        return $this->configData->getUpgradeKey()
               || (UpgradeDatabaseService::needsUpgrade($this->configData->getDatabaseVersion())
                   || UpgradeAppService::needsUpgrade($this->configData->getAppVersion()));
    }

    /**
     * Inicializar la sesión de usuario
     *
     */
    private function initUserSession(): void
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
            } elseif (!$inMaintenance
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
    private function getSessionLifeTime(): int
    {
        $timeout = $this->context->getSessionTimeout();

        try {
            if ($this->isIndex || $timeout === null) {
                $userTimeout = $this->getSessionTimeoutForUser($timeout) ?: $this->configData->getSessionTimeout();

                logger('Session timeout: '.$userTimeout);

                return $this->context->setSessionTimeout($userTimeout);
            }
        } catch (Exception $e) {
            processException($e);
        }

        return $timeout;
    }

    /**
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    private function getSessionTimeoutForUser(?int $default = null): ?int
    {
        if ($this->context->isLoggedIn()) {
            $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT);

            if ($itemPreset !== null) {
                $sessionTimeout = $itemPreset->hydrate(SessionTimeout::class);

                if (Address::check(
                    $this->request->getClientAddress(),
                    $sessionTimeout->getAddress(),
                    $sessionTimeout->getMask()
                )) {
                    return $sessionTimeout->getTimeout();
                }
            }
        }

        return $default;
    }

    private static function getUriFor(string $route): string
    {
        return (new Uri(BootstrapWeb::$WEBROOT))->addParam('r', $route)->getUri();
    }
}
