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

namespace SP;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Klein\Klein;
use Klein\Response;
use PHPMailer\PHPMailer\Exception;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigUtil;
use SP\Core\Exceptions\ConfigException;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Language;
use SP\Core\PhpExtensionChecker;
use SP\Http\Request;
use SP\Modules\Api\Init as InitApi;
use SP\Modules\Web\Init as InitWeb;
use SP\Plugin\PluginManager;
use SP\Services\Api\ApiRequest;
use SP\Services\Api\JsonRpcResponse;
use SP\Services\Upgrade\UpgradeConfigService;
use SP\Services\Upgrade\UpgradeUtil;
use SP\Util\Checks;
use SP\Util\Filter;
use SP\Util\VersionUtil;
use Symfony\Component\Debug\Debug;
use Throwable;

defined('APP_ROOT') || die();

/**
 * Class Bootstrap
 *
 * @package SP
 */
final class Bootstrap
{
    /**
     * @var string The current request path relative to the sysPass root (e.g. files/index.php)
     */
    public static $WEBROOT = '';
    /**
     * @var string The full URL to reach sysPass (e.g. https://sub.example.com/syspass/)
     */
    public static $WEBURI = '';
    /**
     * @var string
     */
    public static $SUBURI = '';
    /**
     * @var mixed
     */
    public static $LOCK;
    /**
     * @var bool Indica si la versión de PHP es correcta
     */
    public static $checkPhpVersion;
    /**
     * @var ContainerInterface
     */
    private static $container;
    /**
     * @var Klein
     */
    private $router;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * Bootstrap constructor.
     *
     * @param Container $container
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    private final function __construct(Container $container)
    {
        self::$container = $container;

        // Set the default language
        Language::setLocales('en_US');

        $this->config = $container->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->router = $container->get(Klein::class);
        $this->request = $container->get(Request::class);
        $this->language = $container->get(Language::class);

        $this->initRouter();
    }

    /**
     * Inicializar router
     */
    protected function initRouter()
    {
        $oops = "Oops, it looks like this content does not exist...";

        $this->router->onError(function ($router, $err_msg, $type, $err) {
            logger('Routing error: ' . $err_msg);

            /** @var Exception|Throwable $err */
            logger('Routing error: ' . $err->getTraceAsString());

            /** @var Klein $router */
            $router->response()->body(__($err_msg));
        });

        // Manage requests for api module
        $this->router->respond(['POST'],
            '@/api\.php',
            function ($request, $response, $service) use ($oops) {
                try {
                    logger('API route');

                    $apiRequest = self::$container->get(ApiRequest::class);

                    list($controller, $action) = explode('/', $apiRequest->getMethod());

                    $controllerClass = 'SP\\Modules\\' . ucfirst(APP_MODULE) . '\\Controllers\\' . ucfirst($controller) . 'Controller';
                    $method = $action . 'Action';

                    if (!method_exists($controllerClass, $method)) {
                        logger($controllerClass . '::' . $method);

                        /** @var Response $response */
                        $response->headers()->set('Content-type', 'application/json; charset=utf-8');
                        return $response->body(JsonRpcResponse::getResponseError($oops, JsonRpcResponse::METHOD_NOT_FOUND, $apiRequest->getId()));
                    }

                    $this->initializeCommon();

                    self::$container->get(InitApi::class)
                        ->initialize($controller);

                    logger('Routing call: ' . $controllerClass . '::' . $method);

                    return call_user_func([new $controllerClass(self::$container, $method, $apiRequest), $method]);
                } catch (\Exception $e) {
                    processException($e);

                    /** @var Response $response */
                    $response->headers()->set('Content-type', 'application/json; charset=utf-8');
                    return $response->body(JsonRpcResponse::getResponseException($e, 0));

                } finally {
                    $this->router->skipRemaining();
                }
            }
        );

        // Manage requests for web module
        $this->router->respond(['GET', 'POST'],
            '@(?!/api\.php)',
            function ($request, $response, $service) use ($oops) {
                try {
                    logger('WEB route');

                    /** @var \Klein\Request $request */
                    $route = Filter::getString($request->param('r', 'index/index'));

                    if (!preg_match_all('#(?P<controller>[a-zA-Z]+)(?:/(?P<action>[a-zA-Z]+))?(?P<params>(/[a-zA-Z\d\.]+)+)?#', $route, $matches)) {
                        throw new RuntimeException($oops);
                    }

//                    $app = $matches['app'][0] ?: 'web';
                    $controllerName = $matches['controller'][0];
                    $methodName = !empty($matches['action'][0]) ? $matches['action'][0] . 'Action' : 'indexAction';
                    $methodParams = !empty($matches['params'][0]) ? Filter::getArray(explode('/', trim($matches['params'][0], '/'))) : [];

                    $controllerClass = 'SP\\Modules\\' . ucfirst(APP_MODULE) . '\\Controllers\\' . ucfirst($controllerName) . 'Controller';

                    $this->initializePluginClasses();

                    if (!method_exists($controllerClass, $methodName)) {
                        logger($controllerClass . '::' . $methodName);

                        /** @var Response $response */
                        $response->code(404);

                        throw new RuntimeException($oops);
                    }

                    $this->initializeCommon();

                    switch (APP_MODULE) {
                        case 'web':
                            self::$container->get(InitWeb::class)
                                ->initialize($controllerName);
                            break;
                    }

                    logger('Routing call: ' . $controllerClass . '::' . $methodName . '::' . print_r($methodParams, true));

                    $controller = new $controllerClass(self::$container, $methodName);

                    return call_user_func_array([$controller, $methodName], $methodParams);
                } catch (SessionTimeout $sessionTimeout) {
                    logger('Session timeout', 'DEBUG');
                } catch (\Exception $e) {
                    processException($e);

                    /** @var Response $response */
                    if ($response->status()->getCode() !== 404) {
                        $response->code(503);
                    }

                    return __($e->getMessage());
                }
            }
        );

        // Manejar URLs que no empiecen por '/admin'
//        $this->Router->respond('GET', '!@^/(admin|public|service)',
//            function ($request, $response) {
//                /** @var Response $response */
//                $response->redirect('index.php');
//            }
//        );
    }

    /**
     * @throws ConfigException
     * @throws Core\Exceptions\CheckException
     * @throws InitializationException
     * @throws Services\Upgrade\UpgradeException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Storage\File\FileException
     */
    protected function initializeCommon()
    {
        logger(__FUNCTION__);

        self::$checkPhpVersion = Checks::checkPhpVersion();

        // Initialize authentication variables
        $this->initAuthVariables();

        // Initialize logging
        $this->initPHPVars();

        // Set application paths
        $this->initPaths();

        self::$container->get(PhpExtensionChecker::class)->checkMandatory();

        if (!self::$checkPhpVersion) {
            throw new InitializationException(
                sprintf(__('Required PHP version >= %s <= %s'), '7.0', '7.3'),
                InitializationException::ERROR,
                __u('Please update the PHP version to run sysPass')
            );
        }

        // Check and intitialize configuration
        $this->initConfig();
    }

    /**
     * Establecer variables de autentificación
     */
    private function initAuthVariables()
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
                && preg_match('/Basic\s+(.*)$/i', $server->get('HTTP_AUTHORIZATION'), $matches))
            || ($server->get('REDIRECT_HTTP_AUTHORIZATION') !== null
                && preg_match('/Basic\s+(.*)$/i', $server->get('REDIRECT_HTTP_AUTHORIZATION'), $matches))
        ) {
            list($name, $password) = explode(':', base64_decode($matches[1]), 2);
            $server->set('PHP_AUTH_USER', strip_tags($name));
            $server->set('PHP_AUTH_PW', strip_tags($password));
        }
    }

    /**
     * Establecer el nivel de logging
     */
    public function initPHPVars()
    {
        if (defined('DEBUG') && DEBUG) {
            Debug::enable();
        } else {
            // Set debug mode if an Xdebug session is active
            if (($this->router->request()->cookies()->get('XDEBUG_SESSION')
                    || $this->configData->isDebug())
                && !defined('DEBUG')
            ) {
                define('DEBUG', true);
                Debug::enable();
            } else {
                error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
                ini_set('display_errors', 0);
            }
        }

        if (!file_exists(LOG_FILE)
            && touch(LOG_FILE)
            && chmod(LOG_FILE, 0600)
        ) {
            logger('Setup log file: ' . LOG_FILE);
        }

        if (date_default_timezone_get() === 'UTC') {
            date_default_timezone_set('UTC');
        }

        // Avoid PHP session cookies from JavaScript
        ini_set('session.cookie_httponly', '1');
        ini_set('session.save_handler', 'files');
    }

    /**
     * Establecer las rutas de la aplicación.
     * Esta función establece las rutas del sistema de archivos y web de la aplicación.
     * La variables de clase definidas son $SERVERROOT, $WEBROOT y $SUBURI
     */
    private function initPaths()
    {
        self::$SUBURI = '/' . basename($this->request->getServer('SCRIPT_FILENAME'));

        $uri = $this->request->getServer('REQUEST_URI');

        $pos = strpos($uri, self::$SUBURI);

        if ($pos > 0) {
            self::$WEBROOT = substr($uri, 0, $pos);
        }

        self::$WEBURI = $this->request->getHttpHost() . self::$WEBROOT;
    }

    /**
     * Cargar la configuración
     *
     * @throws ConfigException
     * @throws Services\Upgrade\UpgradeException
     * @throws Storage\File\FileException
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function initConfig()
    {
        $this->checkConfigVersion();

        ConfigUtil::checkConfigDir();
    }

    /**
     * Comprobar la versión de configuración y actualizarla
     *
     * @throws Services\Upgrade\UpgradeException
     * @throws Storage\File\FileException
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function checkConfigVersion()
    {
        if (file_exists(OLD_CONFIG_FILE)) {
            $upgradeConfigService = self::$container->get(UpgradeConfigService::class);
            $upgradeConfigService->upgradeOldConfigFile(VersionUtil::getVersionStringNormalized());
        }

        $configVersion = UpgradeUtil::fixVersionNumber($this->configData->getConfigVersion());

        if ($this->configData->isInstalled()
            && UpgradeConfigService::needsUpgrade($configVersion)
        ) {
            $upgradeConfigService = self::$container->get(UpgradeConfigService::class);
            $upgradeConfigService->upgrade($configVersion, $this->configData);
        }
    }

    /**
     * initializePluginClasses
     */
    protected function initializePluginClasses()
    {
        $loader = require APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        foreach (PluginManager::getPlugins() as $name => $base) {
            $loader->addPsr4($base['namespace'], $base['dir']);
        }
    }

    /**
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        return self::$container;
    }

    /**
     * @param Container $container
     * @param string    $module
     *
     * @throws InitializationException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function run(Container $container, $module = APP_MODULE)
    {
        $bs = new static($container);

        switch ($module) {
            case 'web':
                logger('------------');
                logger('Boostrap:web');

                $bs->router->dispatch($bs->request->getRequest());
                break;
            case 'api':
                logger('------------');
                logger('Boostrap:api');

                $bs->router->dispatch($bs->request->getRequest());
                break;
            default;
                throw new InitializationException('Unknown module');
        }
    }
}