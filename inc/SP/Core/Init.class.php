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

use SP\Account\AccountAcl;
use SP\Auth\Browser\Browser;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Controller\MainController;
use SP\Core\Exceptions\SPException;
use SP\Core\Plugin\PluginUtil;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Profiles\Profile;
use SP\Storage\DBUtil;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

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
     * Inicializar la aplicación.
     * Esta función inicializa las variables de la aplicación y muestra la página
     * según el estado en el que se encuentre.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    public static function start()
    {
        if (date_default_timezone_get() === 'UTC') {
            date_default_timezone_set('UTC');
        }

        // Variables de autentificación
        self::setAuth();

        // Establecer el nivel de logging
        self::setLogging();

        // Cargar las extensiones
        self::loadExtensions();

        // Iniciar la sesión de PHP
        self::startSession();

        //  Establecer las rutas de la aplicación
        self::setPaths();

        // Cargar la configuración
        self::loadConfig();

        // Cargar el lenguaje
        Language::setLanguage();

        // Establecer el tema de sysPass
        DiFactory::getTheme();

        // Comprobar si es necesario cambiar a HTTPS
        self::checkHttps();

        // Comprobar si es necesario inicialización
        if (self::checkInitSourceInclude() ||
            (defined('IS_INSTALLER') && Checks::isAjax())
        ) {
            return;
        }

        if (!Checks::checkPhpVersion()) {
            self::initError(
                __('Versión de PHP requerida >= ') . ' 5.6.0 <= 7.0',
                __('Actualice la versión de PHP para que la aplicación funcione correctamente'));
        }

        // Volver a cargar la configuración si se recarga la página
        if (Request::checkReload()) {
            Config::loadConfig(true);

            // Restablecer el idioma y el tema visual
            Language::setLanguage(true);
            DiFactory::getTheme()->initTheme(true);

            if (self::isLoggedIn()){
                // Recargar los permisos del perfil de usuario
                Session::setUserProfile(Profile::getItem()->getById(Session::getUserData()->getUserProfileId()));
                // Reset de los datos de ACL de cuentas
                AccountAcl::resetData();
            }
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

        // Comprobar la versión y actualizarla
        self::checkDbVersion();

        // Inicializar la sesión
        self::initSession();

        // Cargar los plugins
        self::loadPlugins();

        // Comprobar acciones en URL
        self::checkPreLoginActions();

        // Intentar establecer el tiempo de vida de la sesión en PHP
        @ini_set('gc_maxlifetime', self::getSessionLifeTime());

        if (!Config::getConfig()->isInstalled()) {
            Session::setUserData();
        }

        // Si es una petición AJAX
        if (Request::analyze('isAjax', false, true)) {
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
    }

    /**
     * Cargar las clases de las extensiones de sysPass
     */
    private static function loadExtensions()
    {
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
     * Iniciar la sesión PHP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function startSession()
    {
        // Evita que javascript acceda a las cookies de sesion de PHP
        ini_set('session.cookie_httponly', '1');

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
     * @param string $hint    opcional, con una ayuda sobre el error
     * @param bool   $headers
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function initError($message, $hint = '', $headers = false)
    {
        debugLog(__FUNCTION__);
        debugLog($message);
        debugLog($hint);

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
     * Cargar la configuración
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function loadConfig()
    {
        // Comprobar si es una versión antigua
        self::checkConfigVersion();

        // Comprobar la configuración y cargar
        self::checkConfig();

        Config::loadConfig();
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
    private static function checkConfig()
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
     * Comprobar el archivo que realiza el include necesita inicialización.
     *
     * @returns bool
     */
    private static function checkInitSourceInclude()
    {
        $srcScript = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
        $skipInit = ['js.php', 'css.php', 'api.php', 'ajax_getEnvironment.php'];

        return in_array($srcScript, $skipInit, true);
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
     */
    private static function checkInstalled()
    {
        // Redirigir al instalador si no está instalada
        if (!Config::getConfig()->isInstalled()) {
            if (self::$SUBURI !== '/index.php') {
                $url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . self::$WEBROOT . '/index.php';
                header("Location: $url");
                exit();
            } else {
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
    }

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     * Devuelve un error 503 y un reintento de 120s al cliente.
     *
     * @param bool $check sólo comprobar si está activado el modo
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function checkMaintenanceMode($check = false)
    {
        if (Config::getConfig()->isMaintenance()) {
            self::$LOCK = Util::getAppLock();

            if ($check === true
                || Checks::isAjax()
                || Request::analyze('upgrade', 0) === 1
                || Request::analyze('nodbupgrade', 0) === 1
                || (self::$LOCK > 0 && self::isLoggedIn() && self::$LOCK === Session::getUserData()->getUserId())
            ) {
                return true;
            }

            self::initError(__('Aplicación en mantenimiento'), __('En breve estará operativa'));
        }

        return false;
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @returns bool
     */
    public static function isLoggedIn()
    {
        return (DiFactory::getDBStorage()->getDbStatus() === 0 && Session::getUserData()->getUserLogin());
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
        $inactiveTime = round((time() - Session::getLastActivity()) / 60, 2);
        $totalTime = round((time() - Session::getStartActivity()) / 60, 2);

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
     * Comrpueba y actualiza la versión de la aplicación.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function checkDbVersion()
    {
        if (self::$SUBURI !== '/index.php' || Request::analyze('logout', 0) === 1) {
            return;
        }

        $update = false;
        $databaseVersion = (int)str_replace('.', '', ConfigDB::getValue('version'));
        $appVersion = (int)implode(Util::getVersion(true));

        if ($databaseVersion < $appVersion
            && Request::analyze('nodbupgrade', 0) === 0
            && Upgrade::needDBUpgrade($databaseVersion)
        ) {
            if (!self::checkMaintenanceMode(true)) {
                $upgradeKey = Config::getConfig()->getUpgradeKey();

                if (empty($upgradeKey)) {
                    Config::getConfig()->setUpgradeKey(sha1(uniqid(mt_rand(), true)));
                    Config::getConfig()->setMaintenance(true);
                    Config::saveConfig(null, false);
                }

                self::initError(__('La aplicación necesita actualizarse'), sprintf(__('Si es un administrador pulse en el enlace: %s'), '<a href="index.php?upgrade=1&a=upgrade">' . __('Actualizar') . '</a>'));
            } else {
                $action = Request::analyze('a');
                $hash = Request::analyze('h');
                $confirm = Request::analyze('chkConfirm', false, false, true);

                if ($confirm === true
                    && $action === 'upgrade'
                    && $hash === Config::getConfig()->getUpgradeKey()
                ) {
                    try {
                        $update = Upgrade::doUpgrade($databaseVersion);

                        ConfigDB::setValue('version', $appVersion);
                        Config::getConfig()->setMaintenance(false);
                        Config::getConfig()->setUpgradeKey('');
                        Config::saveConfig();
                    } catch (SPException $e) {
                        $hint = $e->getHint() . '<p class="center"><a href="index.php?nodbupgrade=1">' . __('Acceder') . '</a></p>';
                        self::initError($e->getMessage(), $hint);
                    }
                } else {
                    $controller = new MainController();
                    $controller->getUpgrade();
                }
            }
        }

        if ($update === true) {
            $Log = new Log();
            $LogMessage = $Log->getLogMessage();
            $LogMessage->setAction(__('Actualización', false));
            $LogMessage->addDescription(__('Actualización de versión realizada.', false));
            $LogMessage->addDetails(__('Versión', false), $appVersion);
            $LogMessage->addDetails(__('Tipo', false), 'db');
            $Log->writeLog();

            Email::sendEmail($LogMessage);

            self::$UPDATED = true;
        }
    }

    /**
     * Inicialiar la sesión de usuario
     */
    private static function initSession()
    {
        $sessionLifeTime = self::getSessionLifeTime();

        // Timeout de sesión
        if (Session::getLastActivity() && (time() - Session::getLastActivity() > $sessionLifeTime)) {
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }

            self::wrLogoutInfo();

            session_unset();
            session_destroy();
            session_start();
            return;
        }

        // Regenerar el Id de sesión periódicamente para evitar fijación
        if (Session::getSidStartTime() === 0) {
            Session::setSidStartTime(time());
            Session::setStartActivity(time());
        } else if (Session::getUserData()->getUserId() > 0 && time() - Session::getSidStartTime() > $sessionLifeTime / 2) {
            $sessionMPass = SessionUtil::getSessionMPass();
            session_regenerate_id(true);
            Session::setSidStartTime(time());
            // Recargar los permisos del perfil de usuario
            Session::setUserProfile(Profile::getItem()->getById(Session::getUserData()->getUserProfileId()));
            // Regenerar la clave maestra
            SessionUtil::saveSessionMPass($sessionMPass);
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
        if (null === Session::getSessionTimeout()) {
            Session::setSessionTimeout(Config::getConfig()->getSessionTimeout());
        }

        return Session::getSessionTimeout();
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

        return true;
    }

    /**
     * Devuelve el tiempo actual en coma flotante.
     * Esta función se utiliza para calcular el tiempo de renderizado con coma flotante
     *
     * @returns float con el tiempo actual
     */
    public static function microtime_float()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }
}