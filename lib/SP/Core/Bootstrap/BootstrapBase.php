<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Bootstrap;

use Closure;
use Klein\Klein;
use Klein\Response;
use PHPMailer\PHPMailer\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigUtil;
use SP\Http\RequestInterface;
use SP\Plugin\PluginManager;
use SP\Util\Checks;
use Symfony\Component\Debug\Debug;
use Throwable;

defined('APP_ROOT') || die();

/**
 * Class BootstrapBase
 *
 * @package SP
 */
abstract class BootstrapBase
{
    public const CONTEXT_ACTION_NAME = "_actionName";

    protected const OOPS_MESSAGE = "Oops, it looks like this content does not exist...";
    /**
     * @var string The current request path relative to the sysPass root (e.g. files/index.php)
     */
    public static string $WEBROOT = '';
    /**
     * @var string The full URL to reach sysPass (e.g. https://sub.example.com/syspass/)
     */
    public static string $WEBURI = '';
    public static string $SUBURI = '';
    /**
     * @var mixed
     */
    public static                 $LOCK;
    public static bool            $checkPhpVersion = false;
    protected Klein               $router;
    protected RequestInterface    $request;
    protected ConfigDataInterface $configData;
    protected ContextInterface    $context;
    private ContainerInterface    $container;
    private UpgradeConfigChecker  $upgradeConfigChecker;
    private PhpExtensionChecker   $phpExtensionChecker;

    /**
     * Bootstrap constructor.
     */
    final public function __construct(
        ConfigDataInterface $configData,
        Klein $router,
        RequestInterface $request,
        UpgradeConfigChecker $upgradeConfigChecker,
        PhpExtensionChecker $extensionChecker,
        ContextInterface $context,
        ContainerInterface $container
    ) {
        // Set the default language
        Language::setLocales('en_US');

        $this->configData = $configData;
        $this->router = $router;
        $this->request = $request;
        $this->upgradeConfigChecker = $upgradeConfigChecker;
        $this->phpExtensionChecker = $extensionChecker;
        $this->context = $context;
        $this->container = $container;

        $this->initRouter();
        $this->configureRouter();
    }

    private function initRouter(): void
    {
        $this->router->onError(function ($router, $err_msg, $type, $err) {
            logger('Routing error: '.$err_msg);

            /** @var Exception|Throwable $err */
            logger('Routing error: '.$err->getTraceAsString());

            /** @var Klein $router */
            $router->response()->body(__($err_msg));
        });

        // Manage requests for options
        $this->router->respond('OPTIONS', null, $this->manageCorsRequest());
    }

    private function manageCorsRequest(): Closure
    {
        return function ($request, $response) {
            /** @var \Klein\Request $request */
            /** @var \Klein\Response $response */

            $this->setCors($response);
        };
    }

    final protected function setCors(Response $response): void
    {
        $response->header(
            'Access-Control-Allow-Origin',
            $this->configData->getApplicationUrl() ?? $this->request->getHttpHost()
        );
        $response->header(
            'Access-Control-Allow-Headers',
            'X-Requested-With, Content-Type, Accept, Origin, Authorization'
        );
        $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    }

    abstract protected function configureRouter(): void;

    abstract public static function run(ContainerInterface $container): BootstrapBase;

    /**
     * @deprecated
     * FIXME: delete
     */
    public static function getContainer()
    {
        return null;
    }

    final protected static function getClassFor(string $controllerName, string $actionName): string
    {
        return sprintf(
            'SP\Modules\%s\Controllers\%s\%sController',
            ucfirst(APP_MODULE),
            ucfirst($controllerName),
            ucfirst($actionName)
        );
    }

    /**
     * Handle the request through the router
     *
     * @return void
     */
    final protected function handleRequest(): void
    {
        $this->router->dispatch($this->request->getRequest());
    }

    /**
     * @throws \SP\Core\Exceptions\CheckException
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Exceptions\InitializationException
     * @throws \SP\Domain\Upgrade\Services\UpgradeException
     */
    final protected function initializeCommon(): void
    {
        logger(__FUNCTION__);

        self::$checkPhpVersion = Checks::checkPhpVersion();

        // Initialize authentication variables
        $this->initAuthVariables();

        // Initialize logging
        $this->initPHPVars();

        // Set application paths
        $this->initPaths();

        $this->phpExtensionChecker->checkMandatory();

        if (!self::$checkPhpVersion) {
            throw new InitializationException(
                sprintf(__('Required PHP version >= %s <= %s'), '7.4', '8.0'),
                SPException::ERROR,
                __u('Please update the PHP version to run sysPass')
            );
        }

        // Check and intitialize configuration
        $this->initConfig();
    }

    /**
     * Establecer variables de autentificación
     */
    private function initAuthVariables(): void
    {
        $server = $this->router->request()->server();

        // Copiar la cabecera http de autentificación para apache+php-fcgid
        if ($server->get('HTTP_XAUTHORIZATION') !== null
            && $server->get('HTTP_AUTHORIZATION') === null) {
            $server->set('HTTP_AUTHORIZATION', $server->get('HTTP_XAUTHORIZATION'));
        }

        // Establecer las cabeceras de autentificación para apache+php-cgi
        // Establecer las cabeceras de autentificación para que apache+php-cgi funcione si la variable es renombrada por apache
        if (($server->get('HTTP_AUTHORIZATION') !== null
             && preg_match(
                 '/Basic\s+(.*)$/i',
                 $server->get('HTTP_AUTHORIZATION'),
                 $matches
             ))
            || ($server->get('REDIRECT_HTTP_AUTHORIZATION') !== null
                && preg_match(
                    '/Basic\s+(.*)$/i',
                    $server->get('REDIRECT_HTTP_AUTHORIZATION'),
                    $matches
                ))
        ) {
            [$name, $password] = explode(
                ':',
                base64_decode($matches[1]),
                2
            );

            $server->set('PHP_AUTH_USER', strip_tags($name));
            $server->set('PHP_AUTH_PW', strip_tags($password));
        }
    }

    /**
     * Establecer el nivel de logging
     */
    private function initPHPVars(): void
    {
        if (DEBUG) {
            /** @noinspection ForgottenDebugOutputInspection */
            Debug::enable();
        } else {
            error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));

            if (!headers_sent()) {
                ini_set('display_errors', 0);
            }
        }

        if (!file_exists(LOG_FILE)
            && touch(LOG_FILE)
            && chmod(LOG_FILE, 0600)
        ) {
            logger('Setup log file: '.LOG_FILE);
        }

        if (date_default_timezone_get() === 'UTC') {
            date_default_timezone_set('UTC');
        }

        if (!headers_sent()) {
            // Avoid PHP session cookies from JavaScript
            ini_set('session.cookie_httponly', '1');
            ini_set('session.save_handler', 'files');
        }
    }

    /**
     * Establecer las rutas de la aplicación.
     * Esta función establece las rutas del sistema de archivos y web de la aplicación.
     * Las variables de clase definidas son $SERVERROOT, $WEBROOT y $SUBURI
     */
    private function initPaths(): void
    {
        self::$SUBURI = '/'.basename($this->request->getServer('SCRIPT_FILENAME'));

        $uri = $this->request->getServer('REQUEST_URI');

        $pos = strpos($uri, self::$SUBURI);

        if ($pos > 0) {
            self::$WEBROOT = substr($uri, 0, $pos);
        }

        self::$WEBURI = $this->request->getHttpHost().self::$WEBROOT;
    }

    /**
     * Cargar la configuración
     *
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Domain\Upgrade\Services\UpgradeException
     */
    private function initConfig(): void
    {
        $this->upgradeConfigChecker->checkConfigVersion();

        ConfigUtil::checkConfigDir();
    }

    final protected function initializePluginClasses(): void
    {
        PluginManager::getPlugins();
    }

    /**
     * @param  string  $class
     *
     * @return object
     */
    final protected function createObjectFor(string $class): object
    {
        try {
            return $this->container->get($class);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            processException($e);

            throw new RuntimeException($e);
        }
    }
}