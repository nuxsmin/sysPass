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
use SP\DataModel\ItemSearchData;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Customers\CustomerSearch;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomFieldDefSearch;
use SP\Mgmt\Files\File;
use SP\Mgmt\Files\FileSearch;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Tags\TagSearch;

/**
 * Class ItemsMgmt para las buśquedas en los listados de elementos de gestión
 *
 * @package SP\Controller
 */
class AppItemsSearchController extends GridItemsSearchController implements ActionsInterface
{
    /**
     * Obtener las cuentas de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getAccounts(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los archivos de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getFiles(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_FILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getFilesGrid();
        $Grid->getData()->setData(FileSearch::getItem()->getMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los campos personalizados de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getCustomFields(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDefSearch::getItem()->getMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los clientes de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getCustomers(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getCustomersGrid();
        $Grid->getData()->setData(CustomerSearch::getItem()->getMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener las categorías de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getCategories(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getCategoriesGrid();
        $Grid->getData()->setData(CategorySearch::getItem()->getMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener las etiquetas de una búsqueda
     *
     * @param ItemSearchData $SearchData
     */
    public function getTags(ItemSearchData $SearchData)
    {
        $this->setAction(self::ACTION_MGM_TAGS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->_grids->getTagsGrid();
        $Grid->getData()->setData(TagSearch::getItem()->getMgmtSearch($SearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $SearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }
}
