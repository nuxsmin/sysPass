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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Template;
use SP\Http\Request;
use SP\Mgmt\Category;
use SP\Mgmt\Customer;
use SP\Mgmt\CustomFieldDef;
use SP\Core\SessionUtil;
use SP\Mgmt\Files;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las vistas de gestión de cuentas
 *
 * @package Controller
 */
class ItemsMgmt extends Controller implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var Grids
     */
    private $_grids;
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

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }

    /**
     * Obtener los datos para la pestaña de categorías
     */
    public function getCategories()
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->_grids->getCategoriesGrid();
        $Grid->getData()->setData(Category::getCategoriesSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de clientes
     */
    public function getCustomers()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->_grids->getCustomersGrid();
        $Grid->getData()->setData(Customer::getCustomersSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->_grids = new Grids();
        $this->_grids->setQueryTimeStart($this->view->queryTimeStart);

        $this->view->addTemplate('datatabs-grid');

        $this->view->assign('tabs', array());
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', Files::getAccountFileList($this->view->accountId));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }

        $this->view->addTemplate('files');

        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la pestaña de campos personalizados
     */
    public function getCustomFields()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->_grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDef::getCustomFieldsSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de archivos
     */
    public function getFiles()
    {
        $this->setAction(self::ACTION_MGM_FILES_VIEW);

        // FIXME: añadir perfil
        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->_grids->getFilesGrid();
        $Grid->getData()->setData(Files::getFileListSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de cuentas
     */
    public function getAccounts()
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->_grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtDataSearch($this->_limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->_limitCount);

        $this->view->append('tabs', $Grid);
    }
}
