<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use JsonException;
use Klein\Klein;
use SP\Core\Application;
use SP\Core\Context\ContextBase;
use SP\Core\Context\Session;
use SP\Core\Context\SessionLifecycleHandler;
use SP\Core\Crypt\Csrf;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\HttpModuleBase;
use SP\Core\Language;
use SP\Core\ProvidersHelper;
use SP\Domain\Common\Providers\Http;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Crypt\CsrfHandler;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\InitializationException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Crypt\Ports\SecureSessionService;
use SP\Domain\Crypt\Services\SecureSession;
use SP\Domain\Http\Adapters\Address;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Providers\Uri;
use SP\Domain\ItemPreset\Models\SessionTimeout;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Services\ItemPreset;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Services\UserProfile;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
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

use function SP\logger;
use function SP\processException;

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


    private Csrf         $csrf;
    private Language      $language;
    private SecureSession $secureSessionService;
    private PluginManager $pluginManager;
    private ItemPreset   $itemPresetService;
    private DatabaseUtil $databaseUtil;
    private UserProfile  $userProfileService;
    private bool         $isIndex = false;

    public function __construct(
        Application                          $application,
        ProvidersHelper                      $providersHelper,
        RequestService       $request,
        Klein                                $router,
        CsrfHandler          $csrf,
        LanguageInterface                    $language,
        SecureSessionService $secureSessionService,
        PluginManager                        $pluginManager,
        ItemPreset           $itemPresetService,
        DatabaseUtil                         $databaseUtil,
        UserProfileService   $userProfileService,
        private readonly UriContextInterface $uriContext
    ) {
        parent::__construct(
            $application,
            $providersHelper,
            $request,
            $router
        );

        $this->csrf = $csrf;
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
     * @param string $controller
     *
     * @throws EnvironmentIsBrokenException
     * @throws JsonException
     * @throws ConstraintException
     * @throws InitializationException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws FileException
     * @throws Exception
     */
    public function initialize(string $controller): void
    {
        logger(__METHOD__);

        $this->isIndex = $controller === 'index';

        $this->context->initialize();

        $isReload = $this->request->checkReload();

        // Volver a cargar la configuración si se recarga la página
        if ($isReload) {
            logger('Browser reload');

            $this->context->setAppStatus(ContextBase::APP_STATUS_RELOADED);
            $this->config->reload();
        }

        // Setup language
        $this->language->setLanguage($isReload);

        // Comprobar si es necesario cambiar a HTTPS
        Http::checkHttps($this->configData, $this->request);

        $partialInit = in_array($controller, self::PARTIAL_INIT, true);

        // Initialize event handlers
        $this->initEventHandlers($partialInit);

        if ($partialInit === false) {
            // Checks if sysPass is installed
            if (!$this->checkInstalled()) {
                logger('Not installed', 'ERROR');

                $this->router->response()->redirect($this->getUriFor(self::ROUTE_INSTALL))->send();

                throw new InitializationException('Not installed');
            }

            // Checks if the database is set up
            if (!$this->databaseUtil->checkDatabaseConnection()) {
                logger('Database connection error', 'ERROR');

                $this->router->response()->redirect($this->getUriFor(self::ROUTE_ERROR_DATABASE_CONNECTION))->send();

                throw new InitializationException('Database connection error');
            }

            // Checks if maintenance mode is turned on
            if ($this->checkMaintenanceMode()) {
                logger('Maintenance mode', 'INFO');

                $this->router->response()->redirect($this->getUriFor(self::ROUTE_ERROR_MAINTENANCE))->send();

                throw new InitializationException('Maintenance mode');
            }

            if ($this->checkUpgradeNeeded()) {
                throw new InitializationException('Upgrade needed');
            }

            // Checks if the database is set up
            if (!$this->databaseUtil->checkDatabaseTables($this->configData->getDbName())) {
                logger('Database checking error', 'ERROR');

                $this->router->response()->redirect($this->getUriFor(self::ROUTE_ERROR_DATABASE))->send();

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
            Session::close();
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

    private function getUriFor(string $route): string
    {
        return (new Uri($this->uriContext->getWebRoot()))->addParam('r', $route)->getUri();
    }

    /**
     * Inicializar la sesión de usuario
     *
     * @throws SPException
     */
    private function initUserSession(): void
    {
        $lastActivity = $this->context->getLastActivity();
        $inMaintenance = $this->configData->isMaintenance();

        // Session timeout
        if ($lastActivity > 0
            && !$inMaintenance
            && time() > $lastActivity + $this->getSessionLifeTime()
        ) {
            SessionLifecycleHandler::restart();
        } else {
            $sidStartTime = $this->context->getSidStartTime();

            // Regenerate session's ID frequently to avoid fixation
            if ($sidStartTime === 0) {
                // Try to set PHP's session lifetime
                @ini_set('session.gc_maxlifetime', $this->getSessionLifeTime());
            } elseif (!$inMaintenance
                      && SessionLifecycleHandler::needsRegenerate($sidStartTime)
                      && $this->context->isLoggedIn()
            ) {
                try {
                    CryptSession::reKey($this->context);
                } catch (CryptException $e) {
                    logger($e->getMessage());

                    SessionLifecycleHandler::restart();

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

                logger('Session timeout: ' . $userTimeout);

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
                )
                ) {
                    return $sessionTimeout->getTimeout();
                }
            }
        }

        return $default;
    }
}
