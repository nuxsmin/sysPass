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
use SP\Mgmt\PublicLinkUtil;
use SP\Mgmt\User\Groups;
use SP\Core\Template;
use SP\Mgmt\User\ProfileUtil;
use SP\Mgmt\User\UserUtil;

/**
 * Clase encargada de de preparar la presentación de las vistas de gestión de accesos
 *
 * @package Controller
 */
class AccItemsMgmt extends GridTabController implements ActionsInterface
{
    /**
     * @var int
     */
    private $_limitCount;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->_limitCount = Config::getValue('account_count');
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

        $Grid = $this->_grids->getUsersGrid();
        $Grid->getData()->setData(UserUtil::getUsersMgmSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

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

        $Grid = $this->_grids->getGroupsGrid();
        $Grid->getData()->setData(Groups::getGroupsMgmtSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

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

        $Grid = $this->_grids->getProfilesGrid();
        $Grid->getData()->setData(ProfileUtil::getProfilesMgmtSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

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

        $Grid = $this->_grids->getTokensGrid();
        $Grid->getData()->setData(ApiTokensUtil::getTokensMgmtSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

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

        $Grid = $this->_grids->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkUtil::getLinksMgmtSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }
}