<?php
/**
 * Created by PhpStorm.
 * User: nuxsmin
 * Date: 2/06/15
 * Time: 22:25
 */

namespace SP\Controller;
use SP\Session;

/**
 * Clase encargada de mostrar el interface principal de la aplicación
 * e interfaces que requieren de un documento html completo
 *
 * @package Controller
 */
class MainC extends Controller implements ActionsInterface
{
    /**
     * Constructor
     *
     * @param $template  \SP\Template con instancia de plantilla
     * @param null $page nombre de página para la clase del body
     */
    public function __construct(\SP\Template $template = null, $page = null)
    {
        parent::__construct($template);

        $this->view->addTemplate('header');
        $this->view->addTemplate('body');

        $this->view->assign('sk', \SP\Common::getSessionKey(true));
        $this->view->assign('appInfo', \SP\Util::getAppInfo());
        $this->view->assign('appVersion', \SP\Util::getVersionString());
        $this->view->assign('startTime', microtime());
        $this->view->assign('page', $page);

        $this->getHeader();
        $this->setHeaders();
    }

    /**
     * Obtener los datos para la cabcera de la página
     */
    public function getHeader()
    {
        $cssVersionHash = md5(implode(\SP\Util::getVersion()) . \SP\Util::resultsCardsIsEnabled());
        $jsVersionHash = md5(implode(\SP\Util::getVersion()));

        $this->view->assign('cssLink', \SP\Init::$WEBROOT . '/css/css.php?v=' . $cssVersionHash);
        $this->view->assign('jsLink', \SP\Init::$WEBROOT . '/js/js.php?v=' . $jsVersionHash);
        $this->view->assign('logo', \SP\Init::$WEBROOT . '/imgs/logo.png');
    }

    /**
     * Establecer las cabeceras HTTP
     */
    private function setHeaders()
    {
        // UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");

        // Cache Control
        header("Cache-Control: public, no-cache, max-age=0, must-revalidate");
        header("Pragma: public; max-age=0");
    }

    /**
     * Obtener los datos para el interface principal de sysPass
     */
    public function getMain()
    {
        $this->view->assign('onLoad', 'doAction(' . self::ACTION_ACC_SEARCH . ')');

        $this->getSessionBar();
        $this->getMenu();

        $this->view->addTemplate('footer');
    }

    /**
     * Obtener los datos para la mostrar la barra de sesión
     */
    private function getSessionBar()
    {
        $this->view->addTemplate('sessionbar');

        $this->view->assign('adminApp', (Session::getUserIsAdminApp()) ? '<span title="' . _('Admin Aplicación') . '">(A+)</span>' : '');
        $this->view->assign('userId', Session::getUserId());
        $this->view->assign('userLogin', strtoupper(Session::getUserLogin()));
        $this->view->assign('userName', (Session::getUserName()) ? Session::getUserName() : strtoupper($this->view->userLogin));
        $this->view->assign('userGroup', Session::getUserGroupName());
        $this->view->assign('showPassIcon', \SP\Session::getUserIsLdap());
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     */
    private function getMenu()
    {
        $this->view->addTemplate('menu');

        $this->view->assign('actions', array(
            array('name' => self::ACTION_ACC_SEARCH, 'title' => _('Buscar'), 'img' => 'search.png', 'checkaccess' => 0),
            array('name' => self::ACTION_ACC_NEW, 'title' => _('Nueva Cuenta'), 'img' => 'add.png', 'checkaccess' => 1),
            array('name' => self::ACTION_USR, 'title' => _('Gestión de Usuarios'), 'img' => 'users.png', 'checkaccess' => 1),
            array('name' => self::ACTION_MGM, 'title' => _('Gestión de Clientes y Categorías'), 'img' => 'appmgmt.png', 'checkaccess' => 1),
            array('name' => self::ACTION_CFG, 'title' => _('Configuración'), 'img' => 'config.png', 'checkaccess' => 1),
            array('name' => self::ACTION_EVL, 'title' => _('Registro de Eventos'), 'img' => 'log.png', 'checkaccess' => 1)
        ));
    }

    /**
     * Obtener los datos para el interface de login
     */
    public function getLogin()
    {
        $this->view->addTemplate('login');
        $this->view->addTemplate('footer');

        $this->view->assign('demoEnabled', \SP\Util::demoIsEnabled());
        $this->view->assign('mailEnabled', \SP\Util::mailIsEnabled());
        $this->view->assign('isLogout', \SP\Request::analyze('logout', false, true));
        $this->view->assign('updated', \SP\Init::$UPDATED === true);
        $this->view->assign('newFeatures', array(
            _('Nuevo interface de búsqueda con estilo de lista o tipo tarjeta'),
            _('Selección de grupos y usuarios de acceso a cuentas'),
            _('Drag&Drop para subida de archivos'),
            _('Copiar clave al portapapeles'),
            _('Historial de cuentas y restauración'),
            _('Nueva gestión de categorías y clientes'),
            _('Función de olvido de claves para usuarios'),
            _('Integración con Active Directory y LDAP mejorada'),
            _('Autentificación para notificaciones por correo'),
            _('Búsqueda global de cuentas para usuarios sin permisos'),
            _('Solicitudes de modificación de cuentas para usuarios sin permisos'),
            _('Importación de cuentas desde KeePass, KeePassX y CSV'),
            _('Función de copiar cuentas'),
            _('Optimización del código y mayor rapidez de carga'),
            _('Mejoras de seguridad en XSS e inyección SQL')
        ));
    }

    /**
     * Obtener los datos para el interface del instalador
     */
    public function getInstaller()
    {
        $this->view->addTemplate('install');
        $this->view->addTemplate('footer');

        $this->view->assign('modulesErrors', \SP\Util::checkModules());
        $this->view->assign('versionErrors', \SP\Util::checkPhpVersion());
        $this->view->assign('resInstall', array());
        $this->view->assign('isCompleted', false);

        if (isset($_POST['install']) && $_POST['install'] == 'true') {
            $this->view->assign('resInstall', \SP_Installer::install($_POST));

            if (count($this->view->resInstall) == 0) {
                $this->view->append('resInstall', array(
                    'type' => 'ok',
                    'description' => _('Instalación finalizada'),
                    'hint' => _('Pulse <a href="index.php" title="Acceder">aquí</a> para acceder')
                ));
                $this->view->assign('isCompleted', true);
            }
        }

        $this->view->assign('securityErrors', array());
    }

    /**
     * Obtener los datos para el interface de error
     *
     * @param bool $showLogo mostrar el logo de sysPass
     */
    public function getError($showLogo = false)
    {
        $this->view->addTemplate('error');
        $this->view->addTemplate('footer');

        $this->view->assign('showLogo', $showLogo);
        $this->view->assign('logo', \SP\Init::$WEBROOT . '/imgs/logo_full.png');
    }

    /**
     * Obtener los datos para el interface de restablecimiento de clave de usuario
     */
    public function getPassReset()
    {
        if (\SP\Util::mailIsEnabled() || \SP\Request::analyze('f', 0) === 1) {
            $this->view->addTemplate('passreset');

            $this->view->assign('action', \SP\Request::analyze('a'));
            $this->view->assign('hash', \SP\Request::analyze('h'));
            $this->view->assign('time', \SP\Request::analyze('t'));
            $this->view->assign('logo', \SP\Init::$WEBROOT . '/imgs/logo_full.png');

            $this->view->assign('passReset', ($this->view->action === 'passreset' && $this->view->hash && $this->view->time));
        } else {
            $this->view->assign('showLogo', true);
            $this->view->assign('logo', \SP\Init::$WEBROOT . '/imgs/logo_full.png');

            $this->showError(self::ERR_UNAVAILABLE, false);
        }

        $this->view->addTemplate('footer');
    }

    /**
     * Obtener los datos para el interface de actualización de BD
     */
    public function getUpgrade()
    {
        $this->view->addTemplate('upgrade');
        $this->view->addTemplate('footer');

        $this->view->assign('action', \SP\Request::analyze('a'));
        $this->view->assign('time', \SP\Request::analyze('t'));
        $this->view->assign('upgrade', $this->view->action === 'upgrade');
        $this->view->assign('logo', \SP\Init::$WEBROOT . '/imgs/logo_full.png');
    }
}