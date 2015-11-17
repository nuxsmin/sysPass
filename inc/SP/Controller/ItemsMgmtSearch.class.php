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

use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Util\Checks;

/**
 * Class ItemsMgmt para las buśquedas en los listados de elementos de gestión
 *
 * @package SP\Controller
 */
class ItemsMgmtSearch extends Controller implements ActionsInterface
{
    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey());
        $this->view->assign('queryTimeStart', microtime());
    }

    /**
     * Obtener las cuentas de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @throws \SP\Core\SPException
     */
    public function getAccounts($search)
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $AccountMgmt = new AccountsMgmtC($this->view);

        $this->view->assign('data', $AccountMgmt->getAccountsGrid($search));
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los archivos de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @throws \SP\Core\SPException
     */
    public function getFiles($search)
    {
        $this->setAction(self::ACTION_MGM_FILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $AccountMgmt = new AccountsMgmtC($this->view);

        $this->view->assign('data', $AccountMgmt->getFilesGrid($search));
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los campos personalizados de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @throws \SP\Core\SPException
     */
    public function getCustomFields($search)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $AccountMgmt = new AccountsMgmtC($this->view);

        $this->view->assign('data', $AccountMgmt->getCustomFieldsGrid($search));
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener los clientes de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @throws \SP\Core\SPException
     */
    public function getCustomers($search)
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $AccountMgmt = new AccountsMgmtC($this->view);

        $this->view->assign('data', $AccountMgmt->getCustomersGrid($search));
        $this->view->assign('actionId', self::ACTION_MGM);
    }

    /**
     * Obtener las categorías de una búsqueda
     *
     * @param string $search La cadena a buscar
     * @throws \SP\Core\SPException
     */
    public function getCategories($search)
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows');

        $AccountMgmt = new AccountsMgmtC($this->view);

        $this->view->assign('data', $AccountMgmt->getCategoriesGrid($search));
        $this->view->assign('actionId', self::ACTION_MGM);
    }
}