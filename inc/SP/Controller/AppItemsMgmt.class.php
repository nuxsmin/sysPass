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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Template;
use SP\Http\Request;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Customers\CustomerSearch;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Core\SessionUtil;
use SP\Mgmt\CustomFields\CustomFieldDefSearch;
use SP\Mgmt\Files\File;
use SP\Mgmt\Files\FileSearch;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Tags\Tags;

/**
 * Clase encargada de preparar la presentación de las vistas de gestión de cuentas
 *
 * @package Controller
 */
class AppItemsMgmt extends GridTabController implements ActionsInterface
{
    /**
     * @var int
     */
    private $limitCount;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->limitCount = Config::getConfig()->getAccountCount();
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

        $Grid = $this->Grids->getCategoriesGrid();
        $Grid->getData()->setData(CategorySearch::getItem()->getMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

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

        $Grid = $this->Grids->getCustomersGrid();
        $Grid->getData()->setData(CustomerSearch::getItem()->getMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', FileUtil::getAccountFiles($this->view->accountId));

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

        $Grid = $this->Grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDefSearch::getItem()->getMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

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

        $Grid = $this->Grids->getFilesGrid();
        $Grid->getData()->setData(FileSearch::getItem()->getMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

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

        $Grid = $this->Grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de etiquetas
     */
    public function getTags()
    {
        $this->setAction(self::ACTION_MGM_TAGS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getTagsGrid();
        $Grid->getData()->setData(Tags::getTagsMgmtSearch($this->limitCount));
        $Grid->updatePager();
        $Grid->getPager()->setOnClickArgs($this->limitCount);

        $this->view->append('tabs', $Grid);
    }
}
