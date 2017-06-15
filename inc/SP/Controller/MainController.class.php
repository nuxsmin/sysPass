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

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Language;
use SP\Core\Messages\NoticeMessage;
use SP\Core\Plugin\PluginUtil;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Task;
use SP\Core\Template;
use SP\Core\Upgrade\Account;
use SP\Core\Upgrade\Check;
use SP\Core\Upgrade\Upgrade;
use SP\DataModel\NoticeData;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\Html;
use SP\Http\Request;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Notices\Notice;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Users\User;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Clase encargada de mostrar el interface principal de la aplicación
 * e interfaces que requieren de un documento html completo
 *
 * @package Controller
 */
class MainController extends ControllerBase implements ActionsInterface
{
    /**
     * Constructor
     *
     * @param        $template   Template con instancia de plantilla
     * @param string $page       El nombre de página para la clase del body
     * @param bool   $initialize Si es una inicialización completa
     */
    public function __construct(Template $template = null, $page = '', $initialize = true)
    {
        parent::__construct($template);

        $this->setPage($page);

        if ($initialize === true) {
            $this->initialize();
        }
    }

    /**
     * Establecer la variable de página de la vista
     *
     * @param $page
     */
    protected function setPage($page)
    {
        $this->view->assign('page', $page);
    }

    /**
     * Inicializar las variables para la vista principal de la aplicación
     */
    protected function initialize()
    {
        $this->view->assign('startTime', microtime());

        $this->view->addTemplate('header');
        $this->view->addTemplate('body-start');

        $this->view->assign('useLayout', true);
        $this->view->assign('isInstalled', Config::getConfig()->isInstalled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('appInfo', Util::getAppInfo());
        $this->view->assign('appVersion', Util::getVersionString());
        $this->view->assign('isDemoMode', Checks::demoIsEnabled());
        $this->view->assign('icons', DiFactory::getTheme()->getIcons());
        $this->view->assign('logoIcon', Init::$WEBURI . '/imgs/logo_icon.png');
        $this->view->assign('logoNoText', Init::$WEBURI . '/imgs/logo_icon.svg');
        $this->view->assign('logo', Init::$WEBURI . '/imgs/logo_full_bg.png');
        $this->view->assign('logonobg', Init::$WEBURI . '/imgs/logo_full_nobg.png');
        $this->view->assign('httpsEnabled', Checks::httpsEnabled());
        $this->view->assign('lang', Init::isLoggedIn() ? Language::$userLang : Language::$globalLang);

        $this->view->assign('loadApp', Session::getAuthCompleted());

        $this->setLoggedIn(Init::isLoggedIn());

        try {
            // Cargar la clave pública en la sesión
            SessionUtil::loadPublicKey();
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);
        } catch (\phpseclib\Exception\FileNotFoundException $e) {
            debugLog($e->getMessage(), true);
        }

        $this->getResourcesLinks();
        $this->setResponseHeaders();
    }

    /**
     * Obtener los datos para la cabcera de la página
     */
    public function getResourcesLinks()
    {
        $version = implode('', Util::getVersion(true));
        $theme = DiFactory::getTheme();

        $jsVersionHash = md5($version);
        $this->view->append('jsLinks', Init::$WEBROOT . '/js/js.php?v=' . $jsVersionHash);
        $this->view->append('jsLinks', Init::$WEBROOT . '/js/js.php?g=1&v=' . $jsVersionHash);

        $themeInfo = $theme->getThemeInfo();

        if (isset($themeInfo['js'])) {
            $themeJsBase = urlencode($theme->getThemePath() . DIRECTORY_SEPARATOR . 'js');
            $themeJsFiles = urlencode(implode(',', $themeInfo['js']));

            $this->view->append('jsLinks', Init::$WEBROOT . '/js/js.php?f=' . $themeJsFiles . '&b=' . $themeJsBase . '&v=' . $jsVersionHash);
        }

        if (Init::isLoggedIn() && Session::getUserPreferences()->getUserId() > 0) {
            $resultsAsCards = Session::getUserPreferences()->isResultsAsCards();
        } else {
            $resultsAsCards = Checks::resultsCardsIsEnabled();
        }

        $cssVersionHash = md5($version . $resultsAsCards);
        $this->view->append('cssLinks', Init::$WEBROOT . '/css/css.php?v=' . $cssVersionHash);

        if (isset($themeInfo['css'])) {
            if ($resultsAsCards) {
                $themeInfo['css'][] = 'search-card.min.css';
            } else {
                $themeInfo['css'][] = 'search-grid.min.css';
            }

            if (Checks::dokuWikiIsEnabled()) {
                $themeInfo['css'][] = 'styles-wiki.min.css';
            }

            $themeCssBase = urlencode($theme->getThemePath() . DIRECTORY_SEPARATOR . 'css');
            $themeCssFiles = urlencode(implode(',', $themeInfo['css']));

            $this->view->append('cssLinks', Init::$WEBROOT . '/css/css.php?f=' . $themeCssFiles . '&b=' . $themeCssBase . '&v=' . $jsVersionHash);
        }

        // Cargar los recursos de los plugins
        foreach (PluginUtil::getLoadedPlugins() as $Plugin) {
            $base = str_replace(Init::$SERVERROOT, '', $Plugin->getBase());
            $jsResources = $Plugin->getJsResources();
            $cssResources = $Plugin->getCssResources();

            if (count($jsResources) > 0) {
                $this->view->append('jsLinks', Init::$WEBROOT . '/js/js.php?f=' . urlencode(implode(',', $jsResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'js') . '&v=' . $jsVersionHash);
            }

            if (count($cssResources) > 0) {
                $this->view->append('cssLinks', Init::$WEBROOT . '/css/css.php?f=' . urlencode(implode(',', $cssResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'css') . '&v=' . $jsVersionHash);
            }
        }
    }

    /**
     * Establecer las cabeceras HTTP
     */
    private function setResponseHeaders()
    {
        // UTF8 Headers
        header('Content-Type: text/html; charset=UTF-8');

        // Cache Control
        header('Cache-Control: public, no-cache, max-age=0, must-revalidate');
        header('Pragma: public; max-age=0');
    }

    /**
     * Obtener los datos para el interface principal de sysPass
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getMain()
    {
        $this->setPage('main');

        $this->getSessionBar();
        $this->getMenu();

        $this->view->addTemplate('body-content');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');
    }

    /**
     * Obtener los datos para la mostrar la barra de sesión
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function getSessionBar()
    {
        $this->view->addTemplate('sessionbar');

        $userType = null;

        if ($this->UserData->isUserIsAdminApp()) {
            $userType = $this->icons->getIconAppAdmin();
        } elseif ($this->UserData->isUserIsAdminAcc()) {
            $userType = $this->icons->getIconAccAdmin();
        }

        $this->view->assign('userType', $userType);
        $this->view->assign('userId', $this->UserData->getUserId());
        $this->view->assign('userLogin', mb_strtoupper($this->UserData->getUserLogin()));
        $this->view->assign('userName', $this->UserData->getUserName() ?: mb_strtoupper($this->view->userLogin));
        $this->view->assign('userGroup', $this->UserData->getUsergroupName());
        $this->view->assign('showPassIcon', !(Config::getConfig()->isLdapEnabled() && $this->UserData->isUserIsLdap()));
        $this->view->assign('userNotices', count(Notice::getItem()->getAllActiveForUser()));
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     */
    private function getMenu()
    {
        $this->view->addTemplate('body-header-menu');

        $ActionSearch = new DataGridAction();
        $ActionSearch->setId(self::ACTION_ACC_SEARCH);
        $ActionSearch->setTitle(__('Buscar'));
        $ActionSearch->setIcon($this->icons->getIconSearch());
        $ActionSearch->setData(['historyReset' => 1, 'view' => 'search']);

        $this->view->append('actions', $ActionSearch);

        if (Acl::checkUserAccess(self::ACTION_ACC_NEW)) {
            $ActionNew = new DataGridAction();
            $ActionNew->setId(self::ACTION_ACC_NEW);
            $ActionNew->setTitle(__('Nueva Cuenta'));
            $ActionNew->setIcon($this->icons->getIconAdd());
            $ActionNew->setData(['historyReset' => 0, 'view' => 'account']);

            $this->view->append('actions', $ActionNew);
        }

        if (Acl::checkUserAccess(self::ACTION_USR)) {
            $ActionUsr = new DataGridAction();
            $ActionUsr->setId(self::ACTION_USR);
            $ActionUsr->setTitle(__('Usuarios y Accesos'));
            $ActionUsr->setIcon($this->icons->getIconAccount());
            $ActionUsr->setData(['historyReset' => 0, 'view' => 'datatabs']);

            $this->view->append('actions', $ActionUsr);
        }

        if (Acl::checkUserAccess(self::ACTION_MGM)) {
            $ActionMgm = new DataGridAction();
            $ActionMgm->setId(self::ACTION_MGM);
            $ActionMgm->setTitle(__('Elementos y Personalización'));
            $ActionMgm->setIcon($this->icons->getIconGroup());
            $ActionMgm->setData(['historyReset' => 0, 'view' => 'datatabs']);

            $this->view->append('actions', $ActionMgm);
        }

        if (Acl::checkUserAccess(self::ACTION_CFG)) {
            $ActionConfig = new DataGridAction();
            $ActionConfig->setId(self::ACTION_CFG);
            $ActionConfig->setTitle(__('Configuración'));
            $ActionConfig->setIcon($this->icons->getIconSettings());
            $ActionConfig->setData(['historyReset' => 1, 'view' => 'config']);

            $this->view->append('actions', $ActionConfig);
        }

        if (Acl::checkUserAccess(self::ACTION_EVL) && Checks::logIsEnabled()) {
            $ActionEventlog = new DataGridAction();
            $ActionEventlog->setId(self::ACTION_EVL);
            $ActionEventlog->setTitle(__('Registro de Eventos'));
            $ActionEventlog->setIcon($this->icons->getIconHeadline());
            $ActionEventlog->setData(['historyReset' => 1, 'view' => 'eventlog']);

            $this->view->append('actions', $ActionEventlog);
        }
    }

    /**
     * Obtener los datos para el interface de login
     */
    public function getLogin()
    {
        $this->setPage('login');

        $this->view->addTemplate('login');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');

        $this->view->assign('useLayout', false);
        $this->view->assign('mailEnabled', Checks::mailIsEnabled());
        $this->view->assign('isLogout', Request::analyze('logout', false, true));
        $this->view->assign('updated', Session::getAppUpdated());

        Session::setAppUpdated(false);

        $getParams = [];

        // Comprobar y parsear los parámetros GET para pasarlos como POST en los inputs
        if (count($_GET) > 0) {
            foreach ($_GET as $param => $value) {
                $getParams['g_' . Html::sanitizeFull($param)] = Html::sanitizeFull($value);
            }
        }

        $this->view->assign('getParams', $getParams);
        $this->view();
        exit();
    }

    /**
     * Obtener los datos para el interface del instalador
     */
    public function getInstaller()
    {
        $this->setPage('install');

        $this->view->addTemplate('body-header');

        $errors = [];

        if (!Checks::checkPhpVersion()) {
            $errors[] = [
                'type' => SPException::SP_CRITICAL,
                'description' => __('Versión de PHP requerida >= ') . ' 5.6.0 <= 7.0',
                'hint' => __('Actualice la versión de PHP para que la aplicación funcione correctamente')
            ];
        }

        $modules = Checks::checkModules();

        if (count($modules) > 0) {
            foreach ($modules as $module) {
                $error[] = [
                    'type' => SPException::SP_WARNING,
                    'description' => sprintf('%s (%s)', __('Módulo no disponible'), $module),
                    'hint' => __('Sin este módulo la aplicación puede no funcionar correctamente.')
                ];
            }
        }

        if (@file_exists(__FILE__ . "\0Nullbyte")) {
            $errors[] = [
                'type' => SPException::SP_WARNING,
                'description' => __('La version de PHP es vulnerable al ataque NULL Byte (CVE-2006-7243)'),
                'hint' => __('Actualice la versión de PHP para usar sysPass de forma segura')];
        }

        if (!Checks::secureRNGIsAvailable()) {
            $errors[] = [
                'type' => SPException::SP_WARNING,
                'description' => __('No se encuentra el generador de números aleatorios.'),
                'hint' => __('Sin esta función un atacante puede utilizar su cuenta al resetear la clave')];
        }

        $this->view->assign('errors', $errors);

        $this->view->assign('langsAvailable', Language::getAvailableLanguages());
        $this->view->assign('langBrowser', Language::$globalLang);

        $this->view->addTemplate('install');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');
    }

    /**
     * Obtener los datos para el interface de error
     */
    public function getError()
    {
        $this->setPage('error');

        if (!Checks::isAjax()) {
            $this->view->addTemplate('body-header');
            $this->view->addTemplate('error');
            $this->view->addTemplate('body-footer');
        } else {
            $this->view->addTemplate('error');
        }

        $this->view();
        exit();
    }

    /**
     * Obtener los datos para el interface de actualización de componentes
     *
     * @param $version
     */
    public function getUpgrade($version)
    {
        $this->setPage('upgrade');

        $this->view->addTemplate('body-header');
        $this->view->addTemplate('upgrade');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');

        $action = Request::analyze('a');
        $type = Request::analyze('type');

        $this->view->assign('action', $action);
        $this->view->assign('type', $type);
        $this->view->assign('version', $version);
        $this->view->assign('upgradeVersion', implode('.', Util::getVersion(true)));
        $this->view->assign('taskId', Task::genTaskId('masterpass'));

        if (Upgrade::checkVersion($version, '130.16011001')) {
            $this->view->assign('checkConstraints', Check::checkConstraints());

            $constraints = [];

            foreach ($this->view->checkConstraints as $key => $val) {
                if ($val > 0) {
                    $constraints[] = sprintf('%s : %s', $key, $val);
                }
            }

            $this->view->assign('constraints', $constraints);
        }

        if (Upgrade::checkVersion($version, '210.17022601')) {
            $this->view->assign('numAccounts', AccountUtil::getTotalNumAccounts());
        }

        $this->view();
        exit();
    }

    /**
     * Obtener los datos para el interface de comprobación de actualizaciones
     */
    public function getCheckUpdates()
    {
        $this->view->addTemplate('update');

        $this->view->assign('hasUpdates', false);
        $this->view->assign('updateStatus', null);

        if (Config::getConfig()->isCheckUpdates()) {
            $updates = Util::checkUpdates();

            if (is_array($updates)) {
                $description = nl2br($updates['description']);
                $version = $updates['version'];

                $this->view->assign('hasUpdates', true);
                $this->view->assign('title', $updates['title']);
                $this->view->assign('url', $updates['url']);
                $this->view->assign('description', sprintf('%s - %s <br><br>%s', __('Descargar nueva versión'), $version, $description));
            } else {
                $this->view->assign('updateStatus', $updates);
            }
        }

        if (Config::getConfig()->isChecknotices()) {
            $notices = Util::checkNotices();
            $numNotices = count($notices);
            $noticesTitle = '';

            if ($notices !== false && $numNotices > 0) {
                $noticesTitle = __('Avisos de sysPass') . '<br>';

                foreach ($notices as $notice) {
                    $noticesTitle .= '<br>' . $notice[0];
                }
            }

            $this->view->assign('numNotices', $numNotices);
            $this->view->assign('noticesTitle', $noticesTitle);
        }
    }

    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     * @throws \phpmailer\phpmailerException
     */
    public function doAction($type = null)
    {
        $this->setPage($type);

        try {
            switch ($type) {
                case 'prelogin.passreset':
                    $this->getPassReset();
                    break;
                case 'prelogin.link':
                    $this->getPublicLink();
                    break;
            }

            DiFactory::getEventDispatcher()->notifyEvent('main.' . $type, $this);
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para el interface de restablecimiento de clave de usuario
     *
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function getPassReset()
    {
        $this->setPage('passreset');

        $this->view->addTemplate('body-header');

        if (Checks::mailIsEnabled() || Request::analyze('f', 0) === 1) {
            $this->view->addTemplate('passreset');

            $this->view->assign('login', Request::analyze('login'));
            $this->view->assign('email', Request::analyze('email'));

            $this->view->assign('action', Request::analyze('a'));
            $this->view->assign('hash', Request::analyze('h'));
            $this->view->assign('time', Request::analyze('t'));

            $this->view->assign('passReset', $this->view->action === 'passreset' && !empty($this->view->hash) && !empty($this->view->time));
        } else {
            $this->showError(self::ERR_UNAVAILABLE, false);
        }

        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');

        $this->view();
        exit();
    }

    /**
     * Obtener la vista para mostrar un enlace publicado
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getPublicLink()
    {
        $this->setPage('publiclink');

        $this->view->addTemplate('body-header', 'main');

        $hash = Request::analyze('h');

        if ($hash) {
            $PublicLink = PublicLink::getItem()->getByHash($hash);

            if (!$PublicLink
                || time() > $PublicLink->getDateExpire()
                || $PublicLink->getCountViews() >= $PublicLink->getMaxCountViews()
            ) {
                $this->showError(self::ERR_PAGE_NO_PERMISSION, false);
            } else {
                PublicLink::getItem($PublicLink)->addLinkView();

                if ($PublicLink->isNotify()) {
                    $Message = new NoticeMessage();
                    $Message->setTitle(__('Enlace visualizado'));
                    $Message->addDescription(sprintf('%s : %s', __('Cuenta'), $PublicLink->getItemId()));
                    $Message->addDescription(sprintf('%s : %s', __('Origen'), Checks::demoIsEnabled() ? '*.*.*.*' : Util::getClientAddress(true)));
                    $Message->addDescription(sprintf('%s : %s', __('Agente'), Request::getRequestHeaders('HTTP_USER_AGENT')));
                    $Message->addDescription(sprintf('HTTPS : %s', Checks::httpsEnabled() ? 'ON' : 'OFF'));


                    $NoticeData = new NoticeData();
                    $NoticeData->setNoticeComponent(__('Cuentas'));
                    $NoticeData->setNoticeDescription($Message);
                    $NoticeData->setNoticeType(__('Información'));
                    $NoticeData->setNoticeUserId($PublicLink->getUserId());

                    Notice::getItem($NoticeData)->add();
                }

                $controller = new AccountController($this->view, $PublicLink->getItemId());
                $controller->getAccountFromLink($PublicLink);
            }

            $this->getSessionBar();
        } else {
            $this->showError(self::ERR_PAGE_NO_PERMISSION, false);
        }

        $this->view->addTemplate('body-footer', 'main');
        $this->view->addTemplate('body-end', 'main');

        $this->view();
        exit();
    }
}