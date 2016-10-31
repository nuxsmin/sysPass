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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Api\ApiTokensUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\Groups\GroupSearch;
use SP\Mgmt\Profiles\ProfileSearch;
use SP\Mgmt\PublicLinks\PublicLinkSearch;
use SP\Core\Template;
use SP\Mgmt\Users\UserSearch;
use SP\Mgmt\Users\UserUtil;

/**
 * Clase encargada de de preparar la presentación de las vistas de gestión de accesos
 *
 * @package Controller
 */
class AccItemsController extends GridTabControllerBase implements ActionsInterface
{
    /**
     * @var ItemSearchData
     */
    private $SearchData;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $ItemSearchData = new ItemSearchData();
        $ItemSearchData->setLimitCount(Config::getConfig()->getAccountCount());
        $this->SearchData = $ItemSearchData;
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     */
    public function getUsersList()
    {
        $this->setAction(self::ACTION_USR_USERS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getUsersGrid();
        $Grid->getData()->setData(UserSearch::getItem()->getMgmtSearch($this->SearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de grupos
     */
    public function getGroupsList()
    {
        $this->setAction(self::ACTION_USR_GROUPS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getGroupsGrid();
        $Grid->getData()->setData(GroupSearch::getItem()->getMgmtSearch($this->SearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de perfiles
     */
    public function getProfilesList()
    {
        $this->setAction(self::ACTION_USR_PROFILES);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getProfilesGrid();
        $Grid->getData()->setData(ProfileSearch::getItem()->getMgmtSearch($this->SearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     */
    public function getAPITokensList()
    {
        $this->setAction(self::ACTION_MGM_APITOKENS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getTokensGrid();
        $Grid->getData()->setData(ApiTokensUtil::getTokensMgmtSearch($this->SearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     */
    public function getPublicLinksList()
    {
        $this->setAction(self::ACTION_MGM_PUBLICLINKS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkSearch::getItem()->getMgmtSearch($this->SearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }
}