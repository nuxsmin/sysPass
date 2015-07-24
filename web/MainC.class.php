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

namespace SP\Controller;

use SP\Init;
use SP\Installer;
use SP\Request;
use SP\Session;
use SP\SPException;
use SP\Util;

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
     * @param      $template  \SP\Template con instancia de plantilla
     * @param null $page      nombre de página para la clase del body
     */
    public function __construct(\SP\Template $template = null, $page = null)
    {
        parent::__construct($template);

        $this->view->addTemplate('header');
        $this->view->addTemplate('body');

        $this->view->assign('sk', \SP\Common::getSessionKey(true));
        $this->view->assign('appInfo', Util::getAppInfo());
        $this->view->assign('appVersion', Util::getVersionString());
        $this->view->assign('startTime', microtime());
        $this->view->assign('page', $page);
        $this->view->assign('loggedIn', \SP\Init::isLoggedIn());
        $this->view->assign('logoNoText', Init::$WEBURI . '/imgs/logo.svg');
        $this->view->assign('logo', Init::$WEBURI . '/imgs/logo_full.svg');

        $this->getHeader();
        $this->setHeaders();
    }

    /**
     * Obtener los datos para la cabcera de la página
     */
    public function getHeader()
    {
        $cssVersionHash = md5(implode(Util::getVersion()) . Util::resultsCardsIsEnabled());
        $jsVersionHash = md5(implode(Util::getVersion()));

        $this->view->assign('cssLink', Init::$WEBROOT . '/css/css.php?v=' . $cssVersionHash);
        $this->view->assign('jsLink', Init::$WEBROOT . '/js/js.php?v=' . $jsVersionHash);
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
        $this->view->assign('showPassIcon', !Session::getUserIsLdap());
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     */
    private function getMenu()
    {
        $this->view->addTemplate('menu');

        $this->view->assign('actions', array(
            array(
                'name' => self::ACTION_ACC_SEARCH,
                'title' => _('Buscar'),
                'img' => 'search.png',
                'icon' => 'search',
                'checkaccess' => 0),
            array(
                'name' => self::ACTION_ACC_NEW,
                'title' => _('Nueva Cuenta'),
                'img' => 'add.png',
                'icon' => 'add',
                'checkaccess' => 1),
            array(
                'name' => self::ACTION_USR,
                'title' => _('Gestión de Usuarios'),
                'img' => 'users.png',
                'icon' => 'account_box',
                'checkaccess' => 1),
            array(
                'name' => self::ACTION_MGM,
                'title' => _('Gestión de Clientes y Categorías'),
                'img' => 'appmgmt.png',
                'icon' => 'group_work',
                'checkaccess' => 1),
            array(
                'name' => self::ACTION_CFG,
                'title' => _('Configuración'),
                'img' => 'config.png',
                'icon' => 'settings_applications',
                'checkaccess' => 1),
            array(
                'name' => self::ACTION_EVL,
                'title' => _('Registro de Eventos'),
                'img' => 'log.png',
                'icon' => 'view_headline',
                'checkaccess' => 1)
        ));
    }

    /**
     * Obtener los datos para el interface de login
     */
    public function getLogin()
    {
        $this->view->addTemplate('login');
        $this->view->addTemplate('footer');

        $this->view->assign('demoEnabled', Util::demoIsEnabled());
        $this->view->assign('mailEnabled', Util::mailIsEnabled());
        $this->view->assign('isLogout', Request::analyze('logout', false, true));
        $this->view->assign('updated', Init::$UPDATED === true);
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
        $this->view->addTemplate('js-common');
        $this->view->addTemplate('footer');

        $this->view->assign('modulesErrors', Util::checkModules());
        $this->view->assign('versionErrors', Util::checkPhpVersion());
        $this->view->assign('securityErrors', array());
        $this->view->assign('resInstall', array());
        $this->view->assign('isCompleted', false);
        $this->view->assign('version', \SP\Util::getVersionString());
        $this->view->assign('adminlogin', Request::analyze('adminlogin', 'admin'));
        $this->view->assign('adminpass', Request::analyze('adminpass', '', false, false, false));
        $this->view->assign('masterpassword', Request::analyze('masterpassword', '', false, false, false));
        $this->view->assign('dbuser', Request::analyze('dbuser', 'root'));
        $this->view->assign('dbpass', Request::analyze('dbpass', '', false, false, false));
        $this->view->assign('dbname', Request::analyze('dbname', 'syspass'));
        $this->view->assign('dbhost', Request::analyze('dbhost', 'localhost'));
        $this->view->assign('hostingmode', Request::analyze('hostingmode', false));

        if (@file_exists(__FILE__ . "\0Nullbyte")) {
            $this->view->append('securityErrors', array(
                    'type' => SPException::SP_WARNING,
                    'description' => _('La version de PHP es vulnerable al ataque NULL Byte (CVE-2006-7243)'),
                    'hint' => _('Actualice la versión de PHP para usar sysPass de forma segura'))
            );
        }

        if (!Util::secureRNG_available()) {
            $this->view->append('securityErrors', array(
                    'type' => SPException::SP_WARNING,
                    'description' => _('No se encuentra el generador de números aleatorios.'),
                    'hint' => _('Sin esta función un atacante puede utilizar su cuenta al resetear la clave'))
            );
        }

        if (Request::analyze('install', false)) {
            Installer::setUsername($this->view->adminlogin);
            Installer::setPassword($this->view->adminpass);
            Installer::setMasterPassword($this->view->masterpassword);
            Installer::setDbuser($this->view->dbuser);
            Installer::setDbpass($this->view->dbpass);
            Installer::setDbname($this->view->dbname);
            Installer::setDbhost($this->view->dbhost);
            Installer::setIsHostingMode($this->view->hostingmode);

            $this->view->assign('resInstall', Installer::install());

            if (count($this->view->resInstall) == 0) {
                $this->view->append('errors', array(
                    'type' => SPException::SP_OK,
                    'description' => _('Instalación finalizada'),
                    'hint' => _('Pulse <a href="index.php" title="Acceder">aquí</a> para acceder')
                ));
                $this->view->assign('isCompleted', true);
                return;
            }
        }

        $this->view->assign('errors', array_merge($this->view->modulesErrors, $this->view->securityErrors, $this->view->resInstall));
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
    }

    /**
     * Obtener los datos para el interface de restablecimiento de clave de usuario
     */
    public function getPassReset()
    {
        if (Util::mailIsEnabled() || Request::analyze('f', 0) === 1) {
            $this->view->addTemplate('passreset');

            $this->view->assign('action', Request::analyze('a'));
            $this->view->assign('hash', Request::analyze('h'));
            $this->view->assign('time', Request::analyze('t'));

            $this->view->assign('passReset', ($this->view->action === 'passreset' && $this->view->hash && $this->view->time));
        } else {
            $this->view->assign('showLogo', true);

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

        $this->view->assign('action', Request::analyze('a'));
        $this->view->assign('time', Request::analyze('t'));
        $this->view->assign('upgrade', $this->view->action === 'upgrade');
    }
}