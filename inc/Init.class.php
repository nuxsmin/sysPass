<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP;

use SP\Controller\ActionsInterface;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase Init para la inicialización del entorno de sysPass
 *
 * @package SP
 */
class Init
{
    /**
     * @var array Associative array for autoloading. classname => filename
     */
    public static $CLASSPATH = array();

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
     * @var string
     */
    private static $_SUBURI = '';

    /**
     * Inicializar la aplicación.
     * Esta función inicializa las variables de la aplicación y muestra la página
     * según el estado en el que se encuentre.
     */
    public static function start()
    {
        self::setIncludes();

        if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
            // Registro del cargador de clases (PHP >= 5.1.2)
            if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                spl_autoload_register('SP\Init::loadClass', true);
            } else {
                spl_autoload_register(array('SP\Init', 'loadClass'));
            }
        } else {
            /**
             * Fall back to traditional autoload for old PHP versions
             *
             * @param string $classname The name of the class to load
             */
            function __autoload($classname)
            {
                \SP\Init::loadClass($classname);
            }
        }

        error_reporting(E_ALL | E_STRICT);

        if (defined('DEBUG') && DEBUG) {
            ini_set('display_errors', 1);
        }

        date_default_timezone_set('UTC');

        // Intentar desactivar magic quotes.
        if (get_magic_quotes_gpc() == 1) {
            ini_set('magic_quotes_runtime', 0);
        }

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

        // Establecer el modo debug si una sesión de xdebug está activa
        if (isset($_COOKIE['XDEBUG_SESSION']) && !defined('DEBUG')) {
            define('DEBUG', true);
        }

        // Establecer el nivel de logging
        if (defined('DEBUG') && DEBUG) {
//            error_log('sysPass DEBUG');
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
            ini_set('display_errors', 'Off');
        }

        // Cargar las extensiones
        self::loadExtensions();

        // Iniciar la sesión de PHP
        self::startSession();

        //  Establecer las rutas de la aplicación
        self::setPaths();

        // Cargar el lenguaje
        Language::setLanguage();

        // Establecer el tema de sysPass
        Themes::setTheme();

        // Comprobar si es necesario cambiar a HTTPS
        self::checkHttps();

        // Comprobar si es necesario inicialización
        if (self::checkInitSourceInclude()) {
            return;
        }

        // Comprobar la configuración
        self::checkConfig();

        // Comprobar si está instalado
        self::checkInstalled();

        // Comprobar si el modo mantenimiento está activado
        self::checkMaintenanceMode();

        // Comprobar si la Base de datos existe
        if (!DB::checkDatabaseExist()) {
            self::initError(_('Error en la verificación de la base de datos'));
        }

        // Comprobar si es cierre de sesión
        self::checkLogout();

        // Comprobar la versión y actualizarla
        self::checkVersion();

        // Inicializar la sesión
        self::initSession();

        // Comprobar acciones en URL
        self::checkPreLoginActions();

        // Intentar establecer el tiempo de vida de la sesión en PHP
        $sessionLifeTime = self::getSessionLifeTime();
        @ini_set('gc_maxlifetime', (string)$sessionLifeTime);

        if (!Config::getValue("installed", false)) {
            Session::setUserId('');
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && Session::getUserId()
            && $_SERVER['PHP_AUTH_USER'] != Session::getUserId()
        ) {
            self::logout();
        }

        // Manejar la redirección para usuarios logeados
        if (Request::analyze('redirect_url', '', true) && self::isLoggedIn()) {
            $location = 'index.php';

            // Denegar la regirección si la URL contiene una @
            // Esto previene redirecciones como ?redirect_url=:user@domain.com
            if (strpos($location, '@') === false) {
                header('Location: ' . $location);
                return;
            }
        }

        // Volver a cargar la configuración si se recarga la página
        if (Request::checkReload()){
            Config::readConfig();

            // Restablecer el idioma y el tema visual
            Language::setLanguage();
            Themes::setTheme();
        }

        if (self::isLoggedIn() || Request::analyze('isAjax', false, true)) {
            return;
        }

        // El usuario no está logado y no es una petición, redirigir al login
        self::goLogin();
    }

    /**
     * Establecer las rutas de sysPass en el PATH de PHP
     */
    public static function setIncludes()
    {
        set_include_path(MODEL_PATH . PATH_SEPARATOR . CONTROLLER_PATH . PATH_SEPARATOR . EXTENSIONS_PATH . PATH_SEPARATOR . get_include_path());
    }

    /**
     * Cargador de clases de sysPass
     *
     * @param $class string El nombre de la clase a cargar
     */
    public static function loadClass($class)
    {
        // Eliminar \\ para las clases con namespace definido
        $class = (strripos($class, '\\')) ? substr($class, strripos($class, '\\') + 1) : $class;

        // Buscar la clase en los directorios de include
        foreach (explode(':', get_include_path()) as $includePath) {
            $classFile = $includePath . DIRECTORY_SEPARATOR . $class . '.class.php';
            if (is_readable($classFile)) {
               require $classFile;
            }
        }
    }

    /**
     * Iniciar la sesión PHP
     */
    private static function startSession()
    {
        // Evita que javascript acceda a las cookies de sesion de PHP
        ini_set('session.cookie_httponly', '1');

        // Si la sesión no puede ser iniciada, devolver un error 500
        if (session_start() === false) {

            Log::newLog(_('Sesion'), _('La sesión no puede ser inicializada'));

            header('HTTP/1.1 500 Internal Server Error');

            self::initError(_('La sesión no puede ser inicializada'), _('Consulte con el administrador'));
        }
    }

    /**
     * Devuelve un eror utilizando la plantilla de rror.
     *
     * @param string $str  con la descripción del error
     * @param string $hint opcional, con una ayuda sobre el error
     */
    public static function initError($str, $hint = '')
    {
        $tpl = new Template();
        $tpl->append('errors', array('type' => SPException::SP_CRITICAL, 'description' => $str, 'hint' => $hint));
        $controller = new Controller\MainC($tpl);
        $controller->getError(true);
        $controller->view();
        exit;
    }

    /**
     * Establecer las rutas de la aplicación.
     * Esta función establece las rutas del sistema de archivos y web de la aplicación.
     * La variables de clase definidas son $SERVERROOT, $WEBROOT y $SUBURI
     */
    private static function setPaths()
    {
        // Calcular los directorios raíz
        $dir = (defined(__DIR__)) ? __DIR__ : dirname(__FILE__);

        self::$SERVERROOT = substr($dir, 0, strripos($dir, '/'));

        self::$_SUBURI = str_replace("\\", '/', substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen(self::$SERVERROOT)));

        $scriptName = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (substr($scriptName, -1) == '/') {
            $scriptName .= 'index.php';
            // Asegurar que suburi sigue las mismas reglas que scriptName
            if (substr(self::$_SUBURI, -9) != 'index.php') {
                if (substr(self::$_SUBURI, -1) != '/') {
                    self::$_SUBURI .= '/';
                }
                self::$_SUBURI .= 'index.php';
            }
        }

        $pos = strpos($scriptName, self::$_SUBURI);

        if ($pos === false) {
            $pos = strpos($scriptName, '?');
        }

        self::$WEBROOT = substr($scriptName, 0, $pos);

        if (self::$WEBROOT != '' && self::$WEBROOT[0] !== '/') {
            self::$WEBROOT = '/' . self::$WEBROOT;
        }

        $protocol = (isset($_SERVER['HTTPS'])) ? 'https://' : 'http://';
        self::$WEBURI .= $protocol . $_SERVER['HTTP_HOST'] . self::$WEBROOT;
    }

    /**
     * Comprobar el archivo que realiza el include necesita inicialización.
     *
     * @returns bool
     */
    private static function checkInitSourceInclude()
    {
        $srcScript = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_BASENAME);
        $skipInit = array('js.php', 'css.php', 'api.php', 'ajax_getEnvironment.php');

        return (in_array($srcScript, $skipInit));
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     */
    private static function checkConfig()
    {
        if (!is_dir(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config')) {
            clearstatcache();
            self::initError(_('El directorio "/config" no existe'));
        }

        if (!is_writable(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config')) {
            clearstatcache();
            self::initError(_('No es posible escribir en el directorio "config"'));
        }

        //$configPerms = substr(sprintf('%o', fileperms(self::$SERVERROOT.'/config')), -4);
        $configPerms = decoct(fileperms(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config') & 0777);

        if (!Util::runningOnWindows() && $configPerms != "750") {
            clearstatcache();
            self::initError(_('Los permisos del directorio "/config" son incorrectos'), _('Actual:') . ' ' . $configPerms . ' - ' . _('Necesario: 750'));
        }
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     */
    private static function checkInstalled()
    {
        // Redirigir al instalador si no está instalada
        if (!Config::getValue('installed', false)) {
            if (self::$_SUBURI != '/index.php') {
                $url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . self::$WEBROOT . '/index.php';
                header("Location: $url");
                exit();
            } elseif (self::$_SUBURI == '/index.php') {
                // Comprobar si sysPass está instalada o en modo mantenimiento
                $controller = new Controller\MainC();
                $controller->getInstaller();
                $controller->view();
                exit();
            }
        }
    }

    /**
     * Comprobar si el modo mantenimeinto está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     * Devuelve un error 503 y un reintento de 120s al cliente.
     *
     * @param bool $check sólo comprobar si está activado el modo
     * @return bool
     */
    public static function checkMaintenanceMode($check = false)
    {
        if (Config::getValue('maintenance', false)) {
            if ($check === true
                || Request::analyze('isAjax', 0) === 1
                || Request::analyze('upgrade', 0) === 1
                || Request::analyze('nodbupgrade', 0) === 1
            ) {
                return true;
            }

            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 120');

            self::initError(_('Aplicación en mantenimiento'), _('En breve estará operativa'));
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

        session_unset();
        session_destroy();
    }

    /**
     * Escribir la información de logout en el registro de eventos.
     */
    private static function wrLogoutInfo()
    {
        $inactiveTime = round(((time() - Session::getLastActivity()) / 60), 2);
        $totalTime = round(((time() - Session::getStartActivity()) / 60), 2);
        $ulogin = Session::getUserLogin();

        $log = new Log(_('Finalizar sesión'));
        $log->addDescription(_('Usuario') . ": " . $ulogin);
        $log->addDescription(_('Tiempo inactivo') . ": " . $inactiveTime . " min.");
        $log->addDescription(_('Tiempo total') . ": " . $totalTime . " min.");
        $log->writeLog();
    }

    /**
     * Mostrar la página de login
     */
    private static function goLogin()
    {
        $controller = new Controller\MainC(null,'login');
        $controller->getLogin();
        $controller->view();
        exit;
    }

    /**
     * Comrpueba y actualiza la versión de la aplicación.
     */
    private static function checkVersion()
    {
        if (substr(self::$_SUBURI, -9) != 'index.php' || Request::analyze('logout', 0) === 1) {
            return;
        }

        $update = false;
        $configVersion = (int)str_replace('.', '', Config::getValue('version'));
        $databaseVersion = (int)str_replace('.', '', ConfigDB::getValue('version'));
        $appVersion = (int)implode(Util::getVersion(true));

        if ($databaseVersion < $appVersion
            && Request::analyze('nodbupgrade', 0) === 0
        ) {
            if (Upgrade::needDBUpgrade($databaseVersion)) {
                if (!self::checkMaintenanceMode(true)) {
                    if (Config::getValue('upgrade_key', 0) === 0) {
                        Config::setValue('upgrade_key', sha1(uniqid(mt_rand(), true)));
                        Config::setValue('maintenance', true);
                    }

                    self::initError(_('La aplicación necesita actualizarse'), sprintf(_('Si es un administrador pulse en el enlace: %s'), '<a href="index.php?upgrade=1&a=upgrade">' . _('Actualizar') . '</a>'));
                }

                $action = Request::analyze('a');
                $hash = Request::analyze('h');

                if ($action === 'upgrade' && $hash === Config::getValue('upgrade_key', 0)) {
                    if (Upgrade::doUpgrade($databaseVersion)) {
                        ConfigDB::setValue('version', $appVersion);
                        Config::setValue('maintenance', false);
                        Config::deleteParam('upgrade_key');
                        $update = true;
                    }
                } else {
                    $controller = new Controller\MainC();
                    $controller->getUpgrade();
                    $controller->view();
                    exit();
                }
            }
        }

        if ($configVersion < $appVersion
            && Upgrade::needConfigUpgrade($appVersion)
            && Upgrade::upgradeConfig($appVersion)
        ) {
            Config::setValue('version', $appVersion);
            $update = true;
        }

        if ($update === true) {
            $log = new Log(_('Actualización'));
            $log->addDescription(_('Actualización de versión realizada.'));
            $log->addDescription(_('Versión') . ': ' . $appVersion);
            $log->writeLog();

            Email::sendEmail($log);

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
        } else if (Session::getUserId() && time() - Session::getSidStartTime() > $sessionLifeTime / 2) {
            $sessionMPass = SessionUtil::getSessionMPass();
            session_regenerate_id(true);
            Session::setSidStartTime(time());
            // Recargar los permisos del perfil de usuario
            Session::setUserProfile(Profile::getProfile(Session::getUserProfileId()));
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
        if (is_null(Session::getSessionTimeout())) {
            Session::setSessionTimeout(Config::getValue('session_timeout', 60 * 5));
        }

        return Session::getSessionTimeout();
    }

    /**
     * Comprobar si hay que ejecutar acciones de URL antes del login.
     *
     * @return bool
     */
    public static function checkPreLoginActions()
    {
        if (!Request::analyze('a', '', true)) {
            return false;
        }

        $action = Request::analyze('a');
        $controller = new Controller\MainC();

        switch ($action) {
            case 'passreset':
                $controller->getPassReset();
                $controller->view();
                break;
            case '2fa':
                $controller->get2FA();
                $controller->view();
                break;
            default:
                return false;
        }

        exit();
    }

    /**
     * Comprobar si hay que ejecutar acciones de URL después del login.
     *
     * @return bool
     */
    public static function checkPostLoginActions()
    {
        if (!Request::analyze('a', '', true)) {
            return false;
        }

        $action = Request::analyze('a');
        $controller = new Controller\MainC(null , 'main');

        switch ($action) {
            case 'accView':
                $itemId = Request::analyze('i');
                $onLoad = 'doAction(' . ActionsInterface::ACTION_ACC_VIEW . ',' . ActionsInterface::ACTION_ACC_SEARCH . ',' . $itemId . ')';
                $controller->getMain($onLoad);
                $controller->view();
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @returns bool
     */
    public static function isLoggedIn()
    {
        if (Session::getUserLogin()
            && Session::get2FApassed()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Devuelve el tiempo actual en coma flotante.
     * Esta función se utiliza para calcular el tiempo de renderizado con coma flotante
     *
     * @returns float con el tiempo actual
     */
    public static function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Cargar las clases de las extensiones de sysPass
     */
    private static function loadExtensions()
    {
        // Utilizar un cargador de clases PSR-0
        require EXTENSIONS_PATH . DIRECTORY_SEPARATOR . 'SplClassLoader.php';

        $phpSecLoader = new \SplClassLoader('phpseclib', EXTENSIONS_PATH);
        $phpSecLoader->register();
    }

    /**
     * Comprobar y forzar (si es necesario) la coneción HTTPS
     */
    private static function checkHttps()
    {
        if(Util::forceHttpsIsEnabled() && !Util::httpsEnabled()){
            $port = ($_SERVER['SERVER_PORT'] != 443) ? ':' . $_SERVER['SERVER_PORT'] : '';
            $fullUrl = 'https://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
            header('Location: ' . $fullUrl);
        }
    }
}