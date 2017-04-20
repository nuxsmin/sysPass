<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Http\Request;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Wiki\DokuWikiApi;

/**
 * Class WikiC para la gestión de la Wiki
 *
 * @package SP\Controller
 */
class WikiController extends ControllerBase implements ActionsInterface
{
    use RequestControllerTrait;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->init();

        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('isDemoMode', Checks::demoIsEnabled() && !Session::getUserData()->isUserIsAdminApp());
        $this->view->assign('isDisabled', (Checks::demoIsEnabled() && !Session::getUserData()->isUserIsAdminApp()) ? 'DISABLED' : '');
    }

    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        try {
            switch ($this->actionId) {
                case self::ACTION_WIKI_VIEW:
                    $this->getWikiPage();
                    break;
                default:
                    $this->invalidAction();
            }

            if (count($this->JsonResponse->getData()) === 0) {
                $this->JsonResponse->setData(['html' => $this->render()]);
            }
        } catch (\Exception $e) {
            $this->JsonResponse->setDescription($e->getMessage());
        }

        $this->JsonResponse->setCsrf($this->view->sk);

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Obtener los datos para la ficha de una página de la Wiki
     */
    public function getWikiPage()
    {
        $pageName = Request::analyze('pageName');

        $this->view->addTemplate('wikipage');

        $pageData = '';
        $pageInfo = '';
        $headerData = '';
        $pageSearch = '';
        $wikiUrlBase = Config::getConfig()->getDokuwikiUrlBase();

        try {
            $DokuWikiApi = new DokuWikiApi();
            $headerData = $DokuWikiApi->getTitle();
            $pageData = $DokuWikiApi->getPage($pageName);

            if ($pageData !== false) {
                if (is_array($pageData) && empty($pageData[0])) {
                    $pageSearch = $DokuWikiApi->getSearch($pageName);
                } else {
                    $pageInfo = $DokuWikiApi->getPageInfo($pageName);
                }
            }
        } catch (SPException $e) {
//            $DokuWikiApi->getPageList();
        }

        $this->view->assign('pageName', $pageName);
        $this->view->assign('wikiUrlBase', $wikiUrlBase);
        $this->view->assign('pageData', $pageData);
        $this->view->assign('pageSearch', $pageSearch);
        $this->view->assign('pageInfo', $pageInfo);
        $this->view->assign('header', $headerData);

        $this->JsonResponse->setStatus(0);
    }
}