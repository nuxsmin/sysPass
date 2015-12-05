<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\SPException;
use SP\Core\Template;
use SP\Util\Checks;
use SP\Util\Wiki\DokuWikiApi;

/**
 * Class WikiC para la gestión de la Wiki
 *
 * @package SP\Controller
 */
class Wiki extends Controller implements ActionsInterface
{
    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('isDemoMode', (Checks::demoIsEnabled() && !Session::getUserIsAdminApp()));
        $this->view->assign('isDisabled', (Checks::demoIsEnabled() && !Session::getUserIsAdminApp()) ? 'DISABLED' : '');
    }

    /**
     * Obtener los datos para la ficha de una página de la Wiki
     *
     * @param string $pageName El nombre de la página
     */
    public function getWikiPage($pageName)
    {
        $this->view->addTemplate('wikipage');

        $pageData = '';
        $pageInfo = '';
        $headerData = '';
        $pageSearch = '';
        $wikiUrlBase = Config::getValue('dokuwiki_urlbase');

        try {
            $DokuWikiApi = new DokuWikiApi();
            $headerData = $DokuWikiApi->getTitle();
            $pageData = $DokuWikiApi->getPage($pageName);
            $pageInfo = $DokuWikiApi->getPageInfo($pageName);

            if (is_array($pageData) && empty($pageData[0])) {
                $pageSearch = $DokuWikiApi->getSearch($pageName);
            }
        } catch (SPException $e) {
        }

        $this->view->assign('pageName', $pageName);
        $this->view->assign('wikiUrlBase', $wikiUrlBase);
        $this->view->assign('pageData', $pageData);
        $this->view->assign('pageSearch', $pageSearch);
        $this->view->assign('pageInfo', $pageInfo);
        $this->view->assign('header', $headerData);
    }
}