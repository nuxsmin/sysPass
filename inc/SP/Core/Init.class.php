<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

use Defuse\Crypto\Exception\CryptoException;
use SP\Account\AccountAcl;
use SP\Auth\Browser\Browser;
use SP\Config\Config;
use SP\Controller\MainController;
use SP\Core\Crypt\SecureKeyCookie;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Exceptions\SPException;
use SP\Core\Plugin\PluginUtil;
use SP\Core\Upgrade\Upgrade;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Profiles\Profile;
use SP\Storage\DBUtil;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;
use SP\Core\Crypt\Session as CryptSession;

defined('APP_ROOT') || die();

/**
 * Clase Init para la inicialización del entorno de sysPass
 *
 * @package SP
 */
class Init
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
     * @var string The sysPass root path for http requests (e.g. syspass/)
     */
    public static $WEBURI = '';

    /**
     * @var bool True if sysPass has been updated. Only for notices.
     */
    public static $UPDATED = false;
    /**
     * @var int
     */
    public static $LOCK = 0;
    /**
     * @var string
     */
    private static $SUBURI = '';
    /**
     * @var bool Indica si la versión de PHP es correcta
     */
    private static $checkPhpVersion;
    /**
     * @var bool Indica si el script requiere inicialización
     */
    private static $checkInitSourceInclude;

    /**
     * Inicializar la aplicación.
     * Esta función inicializa las variables de la aplicación y muestra la página
     * según el estado en el que se encuentre.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function start()
    {
        self::$checkPhpVersion = Checks::checkPhpVersion();
        self::$checkInitSourceInclude = self::checkInitSourceInclude();

        if (date_default_timezone_get() === 'UTC') {
            date_default_timezone_set('UTC');
        }

        // Variables de autentificación
        self::setAuth();

        // Establecer el nivel de logging
        self::setLogging();

        // Cargar las extensiones
        self::loadExtensions();

        // Establecer el lenguaje por defecto
        Language::setLocales('en_US');

        //  Establecer las rutas de la aplicación
        self::setPaths();

        if (!self::$checkPhpVersion && !self::$checkInitSourceInclude) {
            self::initError(
                __('Versión de PHP requerida >= ') . ' 5.6.0 <= 7.0',
                __('Actualice la versión de PHP para que la aplicación funcione correctamente'));
        }

        // Comprobar la configuración
        self::checkConfig();

        // Iniciar la sesión de PHP
        self::startSession(Config::getConfig()->isEncryptSession());

        // Volver a cargar la configuración si se recarga la página
        if (!Request::checkReload()) {
            // Cargar la configuración
            Config::loadConfig();

            // Cargar el lenguaje
            Language::setLanguage();

            // Establecer el tema de sysPass
            DiFactory::getTheme();
        } else {
            // Cargar la configuración
            Config::loadConfig(true);

            // Restablecer el idioma y el tema visual
            Language::setLanguage(true);
            DiFactory::getTheme()->initTheme(true);

            if (self::isLoggedIn() === true) {
                // Recargar los permisos del perfil de usuario
                Session::setUserProfile(Profile::getItem()->getById(Session::getUserData()->getUserProfileId()));
                // Reset de los datos de ACL de cuentas
                AccountAcl::resetData();
            }
        }

        // Comprobar si es necesario cambiar a HTTPS
        self::checkHttps();

        // Comprobar si es necesario inicialización
        if (self::$checkInitSourceInclude ||
            ((defined('IS_INSTALLER') || defined('IS_UPGRADE')) && Checks::isAjax())
        ) {
            return;
        }

        // Comprobar si está instalado
        self::checkInstalled();

        // Comprobar si el modo mantenimiento está activado
        self::checkMaintenanceMode();

        // Comprobar si la Base de datos existe
        if (!DBUtil::checkDatabaseExist()) {
            self::initError(__('Error en la verificación de la base de datos'));
        }

        // Comprobar si es cierre de sesión
        self::checkLogout();

        // Comprobar si es necesario actualizar componentes
        self::checkUpgrade();

        // Inicializar la sesión
        self::initSession();

        // Cargar los plugins
        self::loadPlugins();

        // Comprobar acciones en URL
        self::checkPreLoginActions();

        // Si es una petición AJAX
        if (Checks::isAjax()) {
            return;
        }

        if (self::isLoggedIn() === true && Session::getAuthCompleted() === true) {
            $AuthBrowser = new Browser();

            // Comprobar si se ha identificado mediante el servidor web y el usuario coincide
            if ($AuthBrowser->checkServerAuthUser(Session::getUserData()->getUserLogin()) === false) {
                self::logout();
                // Denegar la redirección si la URL contiene una @
                // Esto previene redirecciones como ?redirect_url=:user@domain.com
            } elseif (Request::analyze('redirect_url', '', true) && strpos('index.php', '@') === false) {
                header('Location: ' . 'index.php');
            }

            return;
        }

        // El usuario no está logado y no es una petición, redirigir al login
        self::goLogin();
    }

    /**
     * Comprobar el archivo que realiza el include necesita inicialización.
     *
     * @returns bool
     */
    private static function checkInitSourceInclude()
    {
        $srcScript = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
        $skipInit = ['js.php', 'css.php', 'api.php', 'ajax_getEnvironment.php', 'ajax_task.php'];

        return in_array($srcScript, $skipInit, true);
    }

    /**
     * Establecer variables de autentificación
     */
    private static function setAuth()
    {
        // Copiar la cabecera http de autentificación para apache+php-fcgid
        if (isset($_SERVER['HTTP_XAUTHORIZATION']) && !isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_XAUTHORIZATION'];
        }

        // Establecer las cabeceras de autentificación para apache+php-cgi
        if (isset($_SERVER['HTTP_AUTHORIZATION'])
            && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)
        ) {
            list($name, $password) = explode(':', base64_decode($matches[1]), 2);
            $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
            $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
        }

        // Establecer las cabeceras de autentificación para que apache+php-cgi funcione si la variable es renombrada por apache
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
            && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)
        ) {
            list($name, $password) = explode(':', base64_decode($matches[1]), 2);
            $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
            $_SERVER['PHP_AUTH_PW'] = strip_tags($password);
        }
    }

    /**
     * Establecer el nivel de logging
     */
    public static function setLogging()
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
    }

    /**
     * Cargar las clases de las extensiones de sysPass
     */
    private static function loadExtensions()
    {
        $CryptoLoader = new \SplClassLoader('Defuse\Crypto', EXTENSIONS_PATH);
        $CryptoLoader->setPrepend(false);
        $CryptoLoader->register();

        $PhpSecLoader = new \SplClassLoader('phpseclib', EXTENSIONS_PATH);
        $PhpSecLoader->setPrepend(false);
        $PhpSecLoader->register();

        $PhpMailerLoader = new \SplClassLoader('phpmailer', EXTENSIONS_PATH);
        $PhpMailerLoader->setPrepend(false);
        $PhpMailerLoader->register();

        $ExtsLoader = new \SplClassLoader('Exts', BASE_DIR);
        $ExtsLoader->setFileExtension('.class.php');
        $ExtsLoader->setPrepend(false);
        $ExtsLoader->register();

        $PluginsLoader = new \SplClassLoader('Plugins', BASE_DIR);
        $PluginsLoader->setFileExtension('.class.php');
        $PluginsLoader->setPrepend(false);
        $PluginsLoader->register();
    }

    /**
     * Establecer las rutas de la aplicación.
     * Esta función establece las rutas del sistema de archivos y web de la aplicación.
     * La variables de clase definidas son $SERVERROOT, $WEBROOT y $SUBURI
     */
    private static function setPaths()
    {
        // Calcular los directorios raíz
        $dir = substr(__DIR__, 0, strpos(__DIR__, str_replace('\\', '/', __NAMESPACE__)) - 1);

        self::$SERVERROOT = substr($dir, 0, strripos($dir, DIRECTORY_SEPARATOR));

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

        $pos = strpos($scriptName, self::$SUBURI);

        if ($pos === false) {
            $pos = strpos($scriptName, '?');
        }

        self::$WEBROOT = substr($scriptName, 0, $pos);

        if (self::$WEBROOT !== '' && self::$WEBROOT[0] !== '/') {
            self::$WEBROOT = '/' . self::$WEBROOT;
        }

        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        self::$WEBURI .= $protocol . $_SERVER['HTTP_HOST'] . self::$WEBROOT;
    }

    /**
     * Iniciar la sesión PHP
     *
     * @param bool $encrypt Encriptar la sesión de PHP
     */
    private static function startSession($encrypt = false)
    {
        // Evita que javascript acceda a las cookies de sesion de PHP
        ini_set('session.cookie_httponly', '1');
        ini_set('session.save_handler', 'files');

        if ($encrypt === true) {
            $Key = SecureKeyCookie::getKey();

            if ($Key !== false && self::$checkPhpVersion) {
                session_set_save_handler(new CryptSessionHandler($Key), true);
            }
        }

        // Si la sesión no puede ser iniciada, devolver un error 500
        if (session_start() === false) {
            Log::writeNewLog(__('Sesión', false), __('La sesión no puede ser inicializada', false));

            header('HTTP/1.1 500 Internal Server Error');

            self::initError(__('La sesión no puede ser inicializada'), __('Consulte con el administrador'));
        }
    }

    /**
     * Devuelve un error utilizando la plantilla de error o en formato JSON
     *
     * @param string $message con la descripción del error
     * @param string $hint opcional, con una ayuda sobre el error
     * @param bool $headers
     */
    public static function initError($message, $hint = '', $headers = false)
    {
        debugLog(__FUNCTION__);
        debugLog(__($message));
        debugLog(__($hint));

        if (Checks::isJson()) {
            $JsonResponse = new JsonResponse();
            $JsonResponse->setDescription($message);
            $JsonResponse->addMessage($hint);
            Json::returnJson($JsonResponse);
        } elseif ($headers === true) {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 120');
        }

        SessionUtil::cleanSession();

        $Tpl = new Template();
        $Tpl->append('errors', ['type' => SPException::SP_CRITICAL, 'description' => __($message), 'hint' => __($hint)]);

        $Controller = new MainController($Tpl, 'error', !Checks::isAjax());
        $Controller->getError();
    }

    /**
     * Cargar la configuración
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function checkConfig()
    {
        // Comprobar si es una versión antigua
        self::checkConfigVersion();

        // Comprobar la configuración y cargar
        self::checkConfigDir();
    }

    /**
     * Comprobar la versión de configuración y actualizarla
     */
    private static function checkConfigVersion()
    {
        $appVersion = (int)implode(Util::getVersion(true));

        if (file_exists(CONFIG_FILE) && Upgrade::upgradeOldConfigFile($appVersion)) {
            self::logConfigUpgrade($appVersion);

            self::$UPDATED = true;

            return;
        }

        $configVersion = (int)Config::getConfig()->getConfigVersion();

        if (Config::getConfig()->isInstalled()
            && $configVersion < $appVersion
            && Upgrade::needConfigUpgrade($configVersion)
            && Upgrade::upgradeConfig($configVersion)
        ) {
            self::logConfigUpgrade($appVersion);

            self::$UPDATED = true;
        }
    }

    /**
     * Registrar la actualización de la configuración
     *
     * @param $version
     */
    private static function logConfigUpgrade($version)
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Actualización', false));
        $LogMessage->addDescription(__('Actualización de versión realizada.', false));
        $LogMessage->addDetails(__('Versión', false), $version);
        $LogMessage->addDetails(__('Tipo', false), 'config');
        $Log->writeLog();

        Email::sendEmail($LogMessage);
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function checkConfigDir()
    {
        if (self::checkInitSourceInclude()) {
            return;
        }

        if (!is_dir(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config')) {
            clearstatcache();
            self::initError(__('El directorio "/config" no existe'));
        }

        if (!is_writable(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config')) {
            clearstatcache();
            self::initError(__('No es posible escribir en el directorio "config"'));
        }

        $configPerms = decoct(fileperms(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config') & 0777);

        if ($configPerms !== '750' && !Checks::checkIsWindows()) {
            clearstatcache();
            self::initError(__('Los permisos del directorio "/config" son incorrectos'), __('Actual:') . ' ' . $configPerms . ' - ' . __('Necesario: 750'));
        }
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @returns bool
     */
    public static function isLoggedIn()
    {
        return (DiFactory::getDBStorage()->getDbStatus() === 0
            && Session::getUserData()->getUserLogin()
            && is_object(Session::getUserPreferences()));
    }

    /**
     * Comprobar y forzar (si es necesario) la conexión HTTPS
     */
    private static function checkHttps()
    {
        if (Checks::forceHttpsIsEnabled() && !Checks::httpsEnabled()) {
            $port = ($_SERVER['SERVER_PORT'] !== 443) ? ':' . $_SERVER['SERVER_PORT'] : '';
            $fullUrl = 'https://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
            header('Location: ' . $fullUrl);
        }
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     */
    private static function checkInstalled()
    {
        // Redirigir al instalador si no está instalada
        if (!Config::getConfig()->isInstalled()) {
            if (self::$SUBURI !== '/index.php') {
                $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

                $url = $protocol . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . self::$WEBROOT . '/index.php';
                header("Location: $url");
                exit();
            }

            if (Session::getAuthCompleted()) {
                session_destroy();

                self::start();
                return;
            }

            // Comprobar si sysPass está instalada o en modo mantenimiento
            $Controller = new MainController();
            $Controller->getInstaller();
            $Controller->view();
            exit();
        }
    }

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     * Devuelve un error 503 y un reintento de 120s al cliente.
     *
     * @param bool $check sólo comprobar si está activado el modo
     * @return bool
     */
    public static function checkMaintenanceMode($check = false)
    {
        if (Config::getConfig()->isMaintenance()) {
            self::$LOCK = Util::getAppLock();

            if ($check === true
                || Checks::isAjax()
                || Request::analyze('nodbupgrade', 0) === 1
                || (Request::analyze('a') === 'upgrade' && Request::analyze('type') !== '')
                || (self::$LOCK > 0 && self::isLoggedIn() && self::$LOCK === Session::getUserData()->getUserId())
            ) {
                return true;
            }

            self::initError(__('Aplicación en mantenimiento'), __('En breve estará operativa'));
        }

        return false;
    }

    /**
     * Comprobar si es necesario cerrar la sesión
     */
    private static function checkLogout()
    {
        if (Request::analyze('logout', false, true)) {
            self::logout();
            self::goLogin();
        }
    }

    /**
     * Deslogar el usuario actual y eliminar la información de sesión.
     */
    private static function logout()
    {
        self::wrLogoutInfo();
    }

    /**
     * Escribir la información de logout en el registro de eventos.
     */
    private static function wrLogoutInfo()
    {
        $inactiveTime = abs(round((time() - Session::getLastActivity()) / 60, 2));
        $totalTime = abs(round((time() - Session::getStartActivity()) / 60, 2));

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Finalizar sesión', false));
        $LogMessage->addDetails(__('Usuario', false), Session::getUserData()->getUserLogin());
        $LogMessage->addDetails(__('Tiempo inactivo', false), $inactiveTime . ' min.');
        $LogMessage->addDetails(__('Tiempo total', false), $totalTime . ' min.');
        $Log->writeLog();
    }

    /**
     * Mostrar la página de login
     */
    private static function goLogin()
    {
        SessionUtil::cleanSession();

        $Controller = new MainController();
        $Controller->getLogin();
    }

    /**
     * Comprobar si es necesario actualizar componentes
     */
    private static function checkUpgrade()
    {
        return self::$SUBURI === '/index.php' && (Upgrade::checkDbVersion() || Upgrade::checkAppVersion());
    }

    /**
     * Inicializar la sesión de usuario
     *
     */
    private static function initSession()
    {
        $lastActivity = Session::getLastActivity();
        $inMaintenance = Config::getConfig()->isMaintenance();

        // Timeout de sesión
        if ($lastActivity > 0
            && !$inMaintenance
            && (time() - $lastActivity) > self::getSessionLifeTime()
        ) {
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }

            self::wrLogoutInfo();

            SessionUtil::restart();
            return;
        }

        $sidStartTime = Session::getSidStartTime();

        // Regenerar el Id de sesión periódicamente para evitar fijación
        if ($sidStartTime === 0) {
            // Intentar establecer el tiempo de vida de la sesión en PHP
            @ini_set('session.gc_maxlifetime', self::getSessionLifeTime());

            Session::setSidStartTime(time());
            Session::setStartActivity(time());
        } else if (!$inMaintenance
            && time() - $sidStartTime > 120
            && Session::getUserData()->getUserId() > 0
        ) {
            try {
                CryptSession::reKey();

                // Recargar los permisos del perfil de usuario
                Session::setUserProfile(Profile::getItem()->getById(Session::getUserData()->getUserProfileId()));
            } catch (CryptoException $e) {
                debugLog($e->getMessage());

                SessionUtil::restart();
                return;
            }
        }

        Session::setLastActivity(time());
    }

    /**
     * Obtener el timeout de sesión desde la configuración.
     *
     * @return int con el tiempo en segundos
     */
    private static function getSessionLifeTime()
    {
        $timeout = Session::getSessionTimeout();

        if (null === $timeout) {
            $configTimeout = Config::getConfig()->getSessionTimeout();
            Session::setSessionTimeout($configTimeout);

            return $configTimeout;
        }

        return $timeout;
    }

    /**
     * Cargar los Plugins disponibles
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function loadPlugins()
    {
        foreach (PluginUtil::getPlugins() as $plugin) {
            $Plugin = PluginUtil::loadPlugin($plugin);

            if ($Plugin !== false) {
                DiFactory::getEventDispatcher()->attach($Plugin);
            }
        }

        Session::setPluginsLoaded(PluginUtil::getLoadedPlugins());
        Session::setPluginsDisabled(PluginUtil::getDisabledPlugins());
    }

    /**
     * Comprobar si hay que ejecutar acciones de URL antes de presentar la pantalla de login.
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     */
    public static function checkPreLoginActions()
    {
        $action = Request::analyze('a');

        if ($action === '') {
            return false;
        }

        $Controller = new MainController();
        $Controller->doAction('prelogin.' . $action);

        return true;
    }

    /**
     * Establecer las rutas de sysPass en el PATH de PHP
     */
    public static function setIncludes()
    {
        set_include_path(MODEL_PATH . PATH_SEPARATOR . CONTROLLER_PATH . PATH_SEPARATOR . EXTENSIONS_PATH . PATH_SEPARATOR . PLUGINS_PATH . PATH_SEPARATOR . get_include_path());
    }

    /**
     * Comprobar si hay que ejecutar acciones de URL después de realizar login.
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     */
    public static function checkPostLoginActions()
    {
        $action = Request::analyze('a');

        if ($action === '') {
            return false;
        }

        $Controller = new MainController();
        $Controller->doAction('postlogin.' . $action);

        return false;
    }
}