<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use Interop\Container\ContainerInterface;
use Klein\Klein;
use Klein\Response;
use PHPMailer\PHPMailer\Exception;
use RuntimeException;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigUtil;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConfigException;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Language;
use SP\Core\UI\Theme;
use SP\Http\Request;
use SP\Modules\Api\Init as InitApi;
use SP\Modules\Web\Init as InitWeb;
use SP\Services\Api\ApiRequest;
use SP\Services\Api\JsonRpcResponse;
use SP\Services\Upgrade\UpgradeConfigService;
use SP\Services\Upgrade\UpgradeUtil;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Class Bootstrap
 *
 * @package SP
 */
final class Bootstrap
{
    /**
     * @var string The installation path on the server (e.g. /srv/www/syspass)
     */
    public static $SERVERROOT = '';
    /**
     * @var string The current request path relative to the sysPass root (e.g. files/index.php)
     */
    public static $WEBROOT = '';
    /**
     * @var string The full URL to reach sysPass (e.g. https://sub.example.com/syspass/)
     */
    public static $WEBURI = '';
    /**
     * @var bool True if sysPass has been updated. Only for notices.
     */
    public static $UPDATED = false;
    /**
     * @var mixed
     */
    public static $LOCK;
    /**
     * @var bool Indica si la versión de PHP es correcta
     */
    public static $checkPhpVersion;
    /**
     * @var string
     */
    public static $SUBURI = '';
    /**
     * @var ContainerInterface|Container
     */
    protected static $container;
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var Theme
     */
    protected $theme;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var Language
     */
    protected $language;
    /**
     * @var Request
     */
    protected $request;
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private final function __construct(Container $container)
    {
        self::$container = $container;

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
        $oops = "Oops, it looks like this content doesn't exist...";

        // Update request when we have a subdirectory
//        $_SERVER['REQUEST_URI'] = self::$WEBROOT;

        $this->router->onError(function ($router, $err_msg, $type, $err) {
            debugLog('Routing error: ' . $err_msg);

            /** @var Exception|\Throwable $err */
            debugLog('Routing error: ' . $err->getTraceAsString());

            /** @var Klein $router */
            $router->response()->body($err_msg);
        });

        // Manejar URLs de módulo web
        $this->router->respond(['POST'],
            '@/api\.php',
            function ($request, $response, $service) use ($oops) {
                try {
                    $apiRequest = self::$container->get(ApiRequest::class);

                    list($controller, $action) = explode('/', $apiRequest->getMethod());

                    $controllerClass = 'SP\\Modules\\' . ucfirst(APP_MODULE) . '\\Controllers\\' . ucfirst($controller) . 'Controller';
                    $method = $action . 'Action';

                    if (!method_exists($controllerClass, $method)) {
                        debugLog($controllerClass . '::' . $method);

                        throw new RuntimeException($oops);
                    }

                    $this->initializeCommon();

                    self::$container->get(InitApi::class)
                        ->initialize($controller);

                    debugLog('Routing call: ' . $controllerClass . '::' . $method);

                    return call_user_func([new $controllerClass(self::$container, $method, $apiRequest), $method]);
                } catch (\Exception $e) {
                    processException($e);

                    /** @var Response $response */
                    $response->headers()->set('Content-type', 'application/json; charset=utf-8');
                    return $response->body(JsonRpcResponse::getResponseException($e, 0));
                }
            }
        );

        // Manejar URLs de módulo web
        $this->router->respond(['GET', 'POST'],
            '@/(index\.php)?',
            function ($request, $response, $service) use ($oops) {
                try {
                    /** @var \Klein\Request $request */
                    $route = filter_var($request->param('r', 'index/index'), FILTER_SANITIZE_STRING);

                    if (!preg_match_all('#(?P<controller>[a-zA-Z]+)(?:/(?P<action>[a-zA-Z]+))?(?P<params>(/[a-zA-Z\d]+)+)?#', $route, $matches)) {
                        throw new RuntimeException($oops);
                    }

//                    $app = $matches['app'][0] ?: 'web';
                    $controller = $matches['controller'][0];
                    $method = !empty($matches['action'][0]) ? $matches['action'][0] . 'Action' : 'indexAction';
                    $params = [];

                    if (!empty($matches['params'][0])) {
                        foreach (explode('/', $matches['params'][0]) as $value) {
                            if (is_numeric($value)) {
                                $params[] = (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                            } elseif (!empty($value)) {
                                $params[] = filter_var($value, FILTER_SANITIZE_STRING);
                            }
                        }
                    }

                    $controllerClass = 'SP\\Modules\\' . ucfirst(APP_MODULE) . '\\Controllers\\' . ucfirst($controller) . 'Controller';

                    if (!method_exists($controllerClass, $method)) {
                        debugLog($controllerClass . '::' . $method);

                        throw new RuntimeException($oops);
                    }

                    $this->initializeCommon();

                    switch (APP_MODULE) {
                        case 'web':
                            self::$container->get(InitWeb::class)
                                ->initialize($controller);
                            break;
                    }

                    debugLog('Routing call: ' . $controllerClass . '::' . $method . '::' . print_r($params, true));

                    return call_user_func_array([new $controllerClass(self::$container, $method), $method], $params);
                } catch (\Exception $e) {
                    processException($e);

                    return $e->getMessage();
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
     * @throws InitializationException
     * @throws Services\Upgrade\UpgradeException
     */
    protected function initializeCommon()
    {
        debugLog(__FUNCTION__);

        self::$checkPhpVersion = Checks::checkPhpVersion();

        // Inicializar autentificación
        $this->initAuthVariables();

        // Inicializar logging
        $this->initPHPVars();

        //  Establecer las rutas de la aplicación
        $this->initPaths();

        // Establecer el lenguaje por defecto
        $this->language->setLocales('en_US');

        if (!self::$checkPhpVersion) {
            throw new InitializationException(
                sprintf(__u('Versión de PHP requerida >= %s <= %s'), '7.0', '7.2'),
                InitializationException::ERROR,
                __u('Actualice la versión de PHP para que la aplicación funcione correctamente')
            );
        }

        // Comprobar la configuración
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
        // Establecer el modo debug si una sesión de xdebug está activa
        if (isset($_COOKIE['XDEBUG_SESSION']) && !defined('DEBUG')) {
            define('DEBUG', true);
        }

        if (defined('DEBUG') && DEBUG) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
            ini_set('display_errors', 'Off');
        }

        if (!file_exists(LOG_FILE) && touch(LOG_FILE) && chmod(LOG_FILE, 0600)) {
            debugLog('Setup log file: ' . LOG_FILE);
        }

        if (date_default_timezone_get() === 'UTC') {
            date_default_timezone_set('UTC');
        }

        // Evita que javascript acceda a las cookies de sesion de PHP
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
        self::$SERVERROOT = dirname(BASE_PATH);

        self::$SUBURI = str_replace("\\", '/', substr(realpath($_SERVER['SCRIPT_FILENAME']), strlen(self::$SERVERROOT)));

        $scriptName = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (substr($scriptName, -1) === '/') {
            $scriptName .= 'index.php';
            // Asegurar que suburi sigue las mismas reglas que scriptName
            if (substr(self::$SUBURI, -9) !== 'index.php') {
                if (substr(self::$SUBURI, -1) !== '/') {
                    self::$SUBURI .= '/';
                }
                self::$SUBURI .= 'index.php';
            }
        }

        if (($pos = strpos($scriptName, self::$SUBURI)) === false) {
            $pos = strpos($scriptName, '?');
        }

        self::$WEBROOT = substr($scriptName, 0, $pos);

        if (self::$WEBROOT !== '' && self::$WEBROOT[0] !== '/') {
            self::$WEBROOT = '/' . self::$WEBROOT;
        }

        self::$WEBURI = $this->request->getHttpHost() . self::$WEBROOT;
    }

    /**
     * Cargar la configuración
     *
     * @throws ConfigException
     * @throws Services\Upgrade\UpgradeException
     */
    private function initConfig()
    {
        // Comprobar si es una versión antigua
        $this->checkConfigVersion();

        ConfigUtil::checkConfigDir();
    }

    /**
     * Comprobar la versión de configuración y actualizarla
     *
     * @throws Services\Upgrade\UpgradeException
     */
    private function checkConfigVersion()
    {
        if (file_exists(OLD_CONFIG_FILE)) {
            $upgradeConfigService = self::$container->get(UpgradeConfigService::class);
            $upgradeConfigService->upgradeOldConfigFile(Util::getVersionStringNormalized());
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function run(Container $container, $module = APP_MODULE)
    {
        $bs = new static($container);

        switch ($module) {
            case 'web':
                debugLog('------------');
                debugLog('Boostrap:web');

                $bs->router->dispatch($bs->request->getRequest());
                break;
            case 'api':
                debugLog('------------');
                debugLog('Boostrap:api');

                $bs->router->dispatch($bs->request->getRequest());
                break;
            default;
                throw new InitializationException('Unknown module');
        }
    }
}