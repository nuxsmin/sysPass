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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));
define ('EXTENSIONS_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ext');
define('DEBUG', false);

class SP_Init
{

    public static $CLASSPATH = array(); // Associative array for autoloading. classname => filename
    public static $SERVERROOT = ''; // The installation path on the server (e.g. /srv/www/syspass)
    public static $WEBROOT = ''; // The current request path relative to the sysPass root (e.g. files/index.php)
    public static $WEBURI = ''; // The sysPass root path for http requests (e.g. syspass/)
    public static $LANG = '';
    public static $UPDATED = false;
    private static $SUBURI = '';

    /**
     * Inicializar la aplicación.
     * Esta función inicializa las variables de la aplicación y muestra la página
     * según el estado en el que se encuentre.
     *
     * @return none
     */
    public static function init()
    {
        if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
            // Registro del cargador de clases (PHP >= 5.1.2)
            if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                spl_autoload_register(array('SP_Init', 'sysPassAutoload'), true, true);
            } else {
                spl_autoload_register(array('SP_Init', 'sysPassAutoload'));
            }
        } else {
            /**
             * Fall back to traditional autoload for old PHP versions
             *
             * @param string $classname The name of the class to load
             */
            function __autoload($classname)
            {
                SP_Init::sysPassAutoload($classname);
            }
        }

        // Establecer el modo debug si una sesión de xdebug está activa
        if (isset($_COOKIE['XDEBUG_SESSION']) && (!defined('DEBUG') || !DEBUG)) {
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

        // Inicar la sesión de PHP
        self::startSession();

        // Establecer las rutas de la aplicación
        self::setPaths();

        // Cargar el lenguaje
        self::selectLang();

        // Comprobar si es necesario inicialización
        if (self::checkInitSourceInclude()) {
            return;
        }

        // Comprobar la configuración
        self::checkConfig();

        // Comprobar si está instalado
        self::checkInstalled();

        // Comprobar si la Base de datos existe
        if (!db::checkDatabaseExist()) {
            self::initError(_('Error en la verificación de la base de datos'));
        }

        // Comprobar si el modo mantenimiento está activado
        self::checkMaintenanceMode();

        // Comprobar la versión y actualizarla
        self::checkVersion();

        // Inicializar las variables de sesión de usuario
        self::initSession();

        // Comprobar acciones en URL
        self::checkRequestActions();

        // Intentar establecer el tiempo de vida de la sesión en PHP
        $sessionLifeTime = SP_Util::getSessionLifeTime();
        @ini_set('gc_maxlifetime', (string)$sessionLifeTime);

        if (!SP_Config::getValue("installed", false)) {
            $_SESSION['user_id'] = '';
        }

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SESSION['user_id'])
            && $_SERVER['PHP_AUTH_USER'] != $_SESSION['user_id']
        ) {
            self::logout();
        }

        // Manejar la redirección para usuarios logeados
        if (isset($_REQUEST['redirect_url']) && self::isLoggedIn()) {
            $location = 'index.php';

            // Denegar la regirección si la URL contiene una @
            // Esto previene redirecciones como ?redirect_url=:user@domain.com
            if (strpos($location, '@') === false) {
                header('Location: ' . $location);
                return;
            }
        }

        // El usuario está logado
        if (self::isLoggedIn()) {
            if (isset($_GET["logout"]) && $_GET["logout"]) {
                self::logout();

                if (count($_GET) > 1) {
                    foreach ($_GET as $param => $value) {
                        if ($param == 'logout') {
                            continue;
                        }

                        $params[] = SP_Html::sanitize($param) . '=' . SP_Html::sanitize($value);
                    }

                    header("Location: " . self::$WEBROOT . '/index.php?' . implode('&', $params));
                } else {
                    header("Location: " . self::$WEBROOT . '/');
                }
            }
            return;
        } else {
            // Si la petición es ajax, no hacer nada
            if ((isset($_POST['isAjax']) || isset($_GET['isAjax']))
                && ($_POST['isAjax'] || $_GET['isAjax'])
            ) {
                return;
            }

            SP_Html::render('login');
            exit();
        }

    }

    /**
     * SPL autoload.
     * Cargador de clases
     */
    public static function sysPassAutoload($classname)
    {
        $class = str_replace("sp_", '', strtolower($classname));
        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . $class . ".class.php";

        if (is_readable($classfile)) {
            require $classfile;
        } else {
            error_log('Class Autoloader Error: ' . $classfile);
        }
    }

    /**
     * Establecer las rutas de la aplicación.
     * Esta función establece las rutas del sistema de archivos y web de la aplicación.
     * La variables de clase definidas son $SERVERROOT, $WEBROOT y $SUBURI
     *
     * @return none
     */
    private static function setPaths()
    {
        // Calcular los directorios raíz
        self::$SERVERROOT = str_replace("\\", DIRECTORY_SEPARATOR, substr(__DIR__, 0, -4));

        // Establecer la ruta include correcta
        set_include_path(self::$SERVERROOT . DIRECTORY_SEPARATOR . 'inc' . PATH_SEPARATOR .
            self::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . PATH_SEPARATOR .
            get_include_path() . PATH_SEPARATOR . self::$SERVERROOT);

        self::$SUBURI = str_replace("\\", '/', substr(realpath($_SERVER["SCRIPT_FILENAME"]), strlen(self::$SERVERROOT)));

        $scriptName = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (substr($scriptName, -1) == '/') {
            $scriptName .= 'index.php';
            // Asegurar que suburi sigue las mismas reglas que scriptName
            if (substr(self::$SUBURI, -9) != 'index.php') {
                if (substr(self::$SUBURI, -1) != '/') {
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

        if (self::$WEBROOT != '' and self::$WEBROOT[0] !== '/') {
            self::$WEBROOT = '/' . self::$WEBROOT;
        }

        self::$WEBURI = (isset($_SERVER['HTTPS'])) ? 'https://' : 'http://';
        self::$WEBURI .= $_SERVER['HTTP_HOST'] . self::$WEBROOT;
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     *
     * @returns none
     */
    private static function selectLang()
    {
        // Comprobamos si el lenguaje ha sido establecido. Si es necesario recargar
        // la configuración, se continúa.
        if (!isset($_SESSION['language']) || SP_Common::parseParams('s', 'reload', 0)){
            $browserLang = str_replace("-", "_", substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
            $configLang = SP_Config::getValue('sitelang');
        } else {
            $browserLang = $configLang = $_SESSION['language'];
        }

        $localesDir = self::$SERVERROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'locales';

        // Establecer a en_US si no existe la traducción o no es español
        if (!file_exists($localesDir . DIRECTORY_SEPARATOR . $browserLang)
            && !preg_match('/^es_.*/i', $browserLang)
            && !$configLang
        ) {
            self::$LANG = 'en_US';
        } else {
            self::$LANG = ($configLang) ? $configLang : $browserLang;
        }

        self::$LANG .= '.utf8';

        putenv('LANG=' . self::$LANG);

        if (defined('LC_MESSAGES')){
            setlocale(LC_MESSAGES, self::$LANG);
        } else {
            putenv('LC_ALL=' . self::$LANG);
            setlocale(LC_ALL, self::$LANG);
        }

        bindtextdomain('messages', $localesDir);
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    /**
     * Comprobar el archivo que realiza el include necesita inicialización.
     *
     * @returns bool
     */
    private static function checkInitSourceInclude()
    {
        $srcScript = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_BASENAME);
        $skipInit = array('js.php', 'css.php');

        return (in_array($srcScript, $skipInit));
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     *
     * @return none
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

        if (!SP_Util::runningOnWindows() && $configPerms != "750") {
            clearstatcache();
            self::initError(_('Los permisos del directorio "/config" son incorrectos'), _('Actual:') . ' ' . $configPerms . ' - ' . _('Necesario: 750'));
        }
    }

    /**
     * Devuelve un eror utilizando la plantilla de rror.
     *
     * @param string $str  con la descripción del error
     * @param string $hint opcional, con una ayuda sobre el error
     * @returns none
     */
    public static function initError($str, $hint = '')
    {
        $errors[] = array(
            'type' => 'critical',
            'description' => $str,
            'hint' => $hint);

        SP_Html::render('error', $errors);
        exit();
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     *
     * @return none
     */
    private static function checkInstalled()
    {
        // Redirigir al instalador si no está instalada
        if (!SP_Config::getValue('installed', false) && self::$SUBURI != '/index.php') {
            $url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . self::$WEBROOT . '/index.php';
            header("Location: $url");
            exit();
        } elseif (!SP_Config::getValue('installed', false) && self::$SUBURI == '/index.php') {
            // Comprobar si sysPass está instalada o en modo mantenimiento
            if (!SP_Config::getValue('installed', false)) {
                SP_Html::render('install');
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
        if (SP_Config::getValue('maintenance', false)) {
            if ($check === true
                || SP_Common::parseParams('r', 'isAjax', 0) === 1
                || SP_Common::parseParams('g', 'upgrade', 0) === 1
                || SP_Common::parseParams('g', 'nodbupgrade', 0) === 1
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
     * Comrpueba y actualiza la versión de la aplicación.
     *
     * @returns none
     */
    private static function checkVersion()
    {
        if (substr(self::$SUBURI, -9) != 'index.php' || SP_Common::parseParams('g', 'logout', 0) === 1) {
            return;
        }

        $update = false;
        $configVersion = (int)str_replace('.', '', SP_Config::getValue('version'));
        $databaseVersion = (int)str_replace('.', '', SP_Config::getConfigValue('version'));
        $appVersion = (int)implode(SP_Util::getVersion(true));

        if ($databaseVersion < $appVersion
            && SP_Common::parseParams('g', 'nodbupgrade', 0) === 0
        ) {
            if (SP_Upgrade::needDBUpgrade($databaseVersion)) {
                if (!self::checkMaintenanceMode(true)) {
                    if (SP_Config::getValue('upgrade_key', 0) === 0) {
                        SP_Config::setValue('upgrade_key', sha1(uniqid(mt_rand(), true)));
                        SP_Config::setValue('maintenance', true);
                    }

                    self::initError(_('La aplicación necesita actualizarse'), _('Si es un administrador pulse en el enlace:') . ' <a href="index.php?upgrade=1&a=upgrade">' . _('Actualizar') . '</a>');
                }

                $action = SP_Common::parseParams('g', 'a');
                $hash = SP_Common::parseParams('g', 'h');

                if ($action === 'upgrade' && $hash === SP_Config::getValue('upgrade_key', 0)) {
                    if (SP_Upgrade::doUpgrade($databaseVersion)) {
                        SP_Config::setConfigValue('version', $appVersion);
                        SP_Config::setValue('maintenance', false);
                        SP_Config::deleteKey('upgrade_key');
                        $update = true;
                    }
                } else {
                    SP_Html::render('upgrade');
                    exit();
                }
            }
        }

        if ($configVersion < $appVersion
            && SP_Upgrade::needConfigUpgrade($appVersion)
            && SP_Upgrade::upgradeConfig($appVersion)
        ) {
            SP_Config::setValue('version', $appVersion);
            $update = true;
        }

        if ($update === true) {
            $message['action'] = _('Actualización');
            $message['text'][] = _('Actualización de versión realizada.');
            $message['text'][] = _('Versión') . ': ' . $appVersion;

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            self::$UPDATED = true;
        }
    }

    /**
     * Inicializar las variables de la sesión de usuario
     *
     * @return none
     */
    private static function initSession()
    {
        $sessionLifeTime = SP_Util::getSessionLifeTime();

        // Regenerar el Id de sesión periódicamente para evitar fijación
        if (!isset($_SESSION['SID_CREATED'])) {
            $_SESSION['SID_CREATED'] = time();
            $_SESSION['START_ACTIVITY'] = time();
        } else if (time() - $_SESSION['SID_CREATED'] > $sessionLifeTime / 2) {
            session_regenerate_id(true);
            $_SESSION['SID_CREATED'] = time();
            // Recargar los permisos del perfil de usuario
            $_SESSION['usrprofile'] = SP_Profiles::getProfileForUser();
            unset($_SESSION['APP_CONFIG']);
        }

        // Timeout de sesión
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $sessionLifeTime)) {
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }

            self::wrLogoutInfo();

            session_unset();
            session_destroy();
            session_start();
        }

        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Escribir la información de logout en el registro de eventos.
     *
     * @return none
     */
    private static function wrLogoutInfo()
    {
        $inactiveTime = round(((time() - $_SESSION['LAST_ACTIVITY']) / 60), 2);
        $totalTime = round(((time() - $_SESSION['START_ACTIVITY']) / 60), 2);
        $ulogin = SP_Common::parseParams('s', 'ulogin');

        $message['action'] = _('Finalizar sesión');
        $message['text'][] = _('Usuario') . ": " . $ulogin;
        $message['text'][] = _('Tiempo inactivo') . ": " . $inactiveTime . " min.";
        $message['text'][] = _('Tiempo total') . ": " . $totalTime . " min.";

        SP_Log::wrLogInfo($message);
    }

    /**
     * Comprobar si hay que ejecutar acciones de URL.
     *
     * @return bool|none
     */
    public static function checkRequestActions()
    {
        if (!SP_Common::parseParams('r', 'a', '', true)) {
            return;
        }

        $action = SP_Common::parseParams('r', 'a');

        switch ($action) {
            case 'passreset':
                SP_Html::render('passreset');
                break;
            default:
                return false;
        }

        exit();
    }

    /**
     * Deslogar el usuario actual y eliminar la información de sesión.
     *
     * @return none
     */
    private static function logout()
    {
        self::wrLogoutInfo();

        session_unset();
        session_destroy();
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @returns bool
     */
    public static function isLoggedIn()
    {
        if (SP_Common::parseParams('s', 'ulogin')) {
            // TODO: refrescar variables de sesión.
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
     * Iniciar la sesión PHP
     */
    private static function startSession(){
        // Evita que javascript acceda a las cookies de sesion de PHP
        ini_set('session.cookie_httponly', '1');

        // Si la sesión no puede ser iniciada, devolver un error 500
        if (session_start() === false) {

            SP_Log::wrLogInfo(_('Sesion'), _('La sesión no puede ser inicializada'));

            header('HTTP/1.1 500 Internal Server Error');
            $errors[] = array(
                'type' => 'critical',
                'description' => _('La sesión no puede ser inicializada'),
                'hint' => _('Consulte con el administrador'));

            SP_Html::render('error', $errors);
            exit();
        }
    }
}

// Empezar a calcular el tiempo y memoria utilizados
$memInit = memory_get_usage();
$time_start = SP_Init::microtime_float();

// Inicializar sysPass
SP_Init::init();
