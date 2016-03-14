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

use SP\Api\ApiTokensUtil;
use SP\Core\ActionsInterface;
use SP\Mgmt\Groups\GroupSearch;
use SP\Mgmt\Profiles\ProfileSearch;
use SP\Mgmt\PublicLinks\PublicLinkSearch;
use SP\Mgmt\Users\UserUtil;

/**
 * Class AccItemsMgmtSearch para la gestión de búsquedas de items de accesos
 *
 * @package SP\Controller
 */
class AccItemsMgmtSearch extends GridItemsSearch implements ActionsInterface
{
    /**
     * Obtener los usuarios de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getUsers($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_USR_USERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getUsersGrid();
        $Grid->getData()->setData(UserUtil::getUsersMgmSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);
    }

    /**
     * Obtener los grupos de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getGroups($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_USR_GROUPS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getGroupsGrid();
        $Grid->getData()->setData(GroupSearch::getItem()->getMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);
    }

    /**
     * Obtener los perfiles de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getProfiles($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_USR_PROFILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getProfilesGrid();
        $Grid->getData()->setData(ProfileSearch::getItem()->getMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);
    }

    /**
     * Obtener los tokens API de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getTokens($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_APITOKENS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getTokensGrid();
        $Grid->getData()->setData(ApiTokensUtil::getTokensMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);
    }

    /**
     * Obtener los enlaces públicos de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getPublicLinks($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_PUBLICLINKS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkSearch::getItem()->getMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);
    }
}