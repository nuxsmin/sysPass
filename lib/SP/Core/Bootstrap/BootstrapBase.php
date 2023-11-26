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

namespace SP\Core\Bootstrap;

use Closure;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use PHPMailer\PHPMailer\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SP\Core\Language;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigUtil;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\ConfigException;
use SP\Domain\Core\Exceptions\InitializationException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Http\RequestInterface;
use SP\Plugin\PluginManager;
use SP\Util\Checks;
use Symfony\Component\Debug\Debug;
use Throwable;

use function SP\__;
use function SP\__u;
use function SP\logger;
use function SP\processException;

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
     * @deprecated Use {@see UriContextInterface::getWebRoot()} instead
     * @var string The current request path relative to the sysPass root (e.g. files/index.php)
     */
    public static string $WEBROOT = '';
    /**
     * @deprecated Use {@see UriContextInterface::getWebUri()} instead
     * @var string The full URL to reach sysPass (e.g. https://sub.example.com/syspass/)
     */
    public static string $WEBURI = '';
    /**
     * @deprecated Use {@see UriContextInterface::getSubUri()} instead
     */
    public static string $SUBURI = '';
    /**
     * @var mixed
     */
    public static      $LOCK;
    public static bool $checkPhpVersion = false;

    /**
     * Bootstrap constructor.
     */
    final public function __construct(
        protected readonly ConfigDataInterface $configData,
        protected readonly Klein               $router,
        protected readonly RequestInterface    $request,
        private readonly UpgradeConfigChecker  $upgradeConfigChecker,
        protected readonly PhpExtensionChecker $extensionChecker,
        protected readonly ContextInterface    $context,
        private readonly ContainerInterface    $container,
        protected readonly UriContextInterface $uriContext
    ) {
        // Set the default language
        Language::setLocales('en_US');

        $this->initRouter();
        $this->configureRouter();
    }

    private function initRouter(): void
    {
        $this->router->onError(function ($router, $err_msg, $type, $err) {
            logger('Routing error: ' . $err_msg);

            /** @var Exception|Throwable $err */
            logger('Routing error: ' . $err->getTraceAsString());

            /** @var Klein $router */
            $router->response()->body(__($err_msg));
        });

        // Manage requests for options
        $this->router->respond('OPTIONS', null, $this->manageCorsRequest());
    }

    private function manageCorsRequest(): Closure
    {
        return function ($request, $response) {
            /** @var Request $request */
            /** @var Response $response */

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
     * @throws CheckException
     * @throws ConfigException
     * @throws InitializationException
     * @throws UpgradeException
     */
    final protected function initializeCommon(): void
    {
        logger(__FUNCTION__);

        self::$checkPhpVersion = Checks::checkPhpVersion();

        // Initialize authentication variables
        $this->initAuthVariables();

        // Initialize logging
        $this->initPHPVars();

        $this->extensionChecker->checkMandatory();

        if (!self::$checkPhpVersion) {
            throw new InitializationException(
                sprintf(__('Required PHP version >= %s <= %s'), '8.1', '8.2'),
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
            && $server->get('HTTP_AUTHORIZATION') === null
        ) {
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
            logger('Setup log file: ' . LOG_FILE);
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
     * Cargar la configuración
     *
     * @throws ConfigException
     * @throws UpgradeException
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
     * @param string $class
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
