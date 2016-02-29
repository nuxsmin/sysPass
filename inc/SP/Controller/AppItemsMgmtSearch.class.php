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

use SP\Account\AccountUtil;
use SP\Core\ActionsInterface;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\Files\Files;
use SP\Mgmt\Tags\Tags;

/**
 * Class ItemsMgmt para las buśquedas en los listados de elementos de gestión
 *
 * @package SP\Controller
 */
class AppItemsMgmtSearch extends GridItemsSearch implements ActionsInterface
{
    /**
     * Obtener las cuentas de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getAccounts($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los archivos de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param        $limitStart
     * @param        $limitCount
     */
    public function getFiles($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_FILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getFilesGrid();
        $Grid->getData()->setData(Files::getFilesMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los campos personalizados de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getCustomFields($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDef::getCustomFieldsMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los clientes de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getCustomers($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getCustomersGrid();
        $Grid->getData()->setData(Customer::getCustomersMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener las categorías de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getCategories($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getCategoriesGrid();
        $Grid->getData()->setData(Category::getCategoriesMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener las etiquetas de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @param int    $limitStart
     * @param int    $limitCount
     */
    public function getTags($search, $limitStart, $limitCount)
    {
        $this->setAction(self::ACTION_MGM_TAGS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $Grid = $this->_grids->getTagsGrid();
        $Grid->getData()->setData(Tags::getTagsMgmtSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }
}
