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

use SP\Account\AccountUtil;
use SP\Core\ActionsInterface;
use SP\Core\Template;
use SP\Html\DataGrid\DataGridPagerBase;
use SP\Mgmt\Category;
use SP\Mgmt\Customer;
use SP\Mgmt\CustomFieldDef;
use SP\Mgmt\Files;
use SP\Util\Checks;

/**
 * Class ItemsMgmt para las buśquedas en los listados de elementos de gestión
 *
 * @package SP\Controller
 */
class ItemsMgmtSearch extends Controller implements ActionsInterface
{
    /**
     * @var Grids
     */
    private $_grids;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());

        $this->_grids = new Grids();
        $this->_grids->setQueryTimeStart(microtime());
    }

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
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtDataSearch($limitCount, $limitStart, $search));
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
        $Grid->getData()->setData(Files::getFileListSearch($limitCount, $limitStart, $search));
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
        $Grid->getData()->setData(CustomFieldDef::getCustomFieldsSearch($limitCount, $limitStart, $search));
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
        $Grid->getData()->setData(Customer::getCustomersSearch($limitCount, $limitStart, $search));
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
        $Grid->getData()->setData(Category::getCategoriesSearch($limitCount, $limitStart, $search));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), !empty($search), $limitStart, $limitCount);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Actualizar los datos del paginador
     *
     * @param DataGridPagerBase $Pager
     * @param bool              $filterOn
     * @param int               $limitStart
     * @param int               $limitCount
     */
    private function updatePager(DataGridPagerBase $Pager, $filterOn, $limitStart, $limitCount)
    {
        $Pager->setLimitStart($limitStart);
        $Pager->setLimitCount($limitCount);
        $Pager->setOnClickArgs($limitCount);
        $Pager->setFilterOn($filterOn);
    }

}
