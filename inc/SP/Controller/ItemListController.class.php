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
use SP\Api\ApiTokensUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\CustomerSearch;
use SP\Mgmt\CustomFields\CustomFieldDefSearch;
use SP\Mgmt\Files\FileSearch;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Groups\GroupSearch;
use SP\Mgmt\Plugins\PluginSearch;
use SP\Mgmt\Profiles\ProfileSearch;
use SP\Mgmt\PublicLinks\PublicLinkSearch;
use SP\Core\Template;
use SP\Mgmt\Tags\TagSearch;
use SP\Mgmt\Users\UserSearch;
use SP\Util\Checks;

/**
 * Clase encargada de de preparar la presentación de las vistas de gestión de accesos
 *
 * @package Controller
 */
class ItemListController extends GridTabControllerBase implements ActionsInterface
{
    const TYPE_ACCESSES = 1;
    const TYPE_ACCOUNTS = 2;

    /**
     * @var ItemSearchData
     */
    private $ItemSearchData;

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
        $this->ItemSearchData = $ItemSearchData;
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->addTemplate('files-list', 'account');

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', FileUtil::getAccountFiles($this->view->accountId));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }
    }

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     * @throws \InvalidArgumentException
     */
    public function doAction($type = null)
    {
        $this->useTabs();

        if ($type === self::TYPE_ACCOUNTS) {
            $this->getCategories();
            $this->getCustomers();
            $this->getCustomFields();
            $this->getFiles();
            $this->getAccounts();
            $this->getTags();
            $this->getPluginsList();

            $this->EventDispatcher->notifyEvent('show.itemlist.accounts', $this);
        } elseif ($type === self::TYPE_ACCESSES) {
            $this->getUsersList();
            $this->getGroupsList();
            $this->getProfilesList();
            $this->getAPITokensList();
            $this->getPublicLinksList();

            $this->EventDispatcher->notifyEvent('show.itemlist.accesses', $this);
        }
    }

    /**
     * Obtener los datos para la pestaña de categorías
     *
     * @throws \InvalidArgumentException
     */
    public function getCategories()
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getCategoriesGrid();
        $Grid->getData()->setData(CategorySearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de clientes
     *
     * @throws \InvalidArgumentException
     */
    public function getCustomers()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getCustomersGrid();
        $Grid->getData()->setData(CustomerSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de campos personalizados
     *
     * @throws \InvalidArgumentException
     */
    public function getCustomFields()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDefSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de archivos
     *
     * @throws \InvalidArgumentException
     */
    public function getFiles()
    {
        if (!Checks::fileIsEnabled()) {
            return;
        }

        $this->setAction(self::ACTION_MGM_FILES);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getFilesGrid();
        $Grid->getData()->setData(FileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de cuentas
     *
     * @throws \InvalidArgumentException
     */
    public function getAccounts()
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de etiquetas
     *
     * @throws \InvalidArgumentException
     */
    public function getTags()
    {
        $this->setAction(self::ACTION_MGM_TAGS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getTagsGrid();
        $Grid->getData()->setData(TagSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     *
     * @throws \InvalidArgumentException
     */
    public function getUsersList()
    {
        $this->setAction(self::ACTION_USR_USERS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getUsersGrid();
        $Grid->getData()->setData(UserSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de grupos
     *
     * @throws \InvalidArgumentException
     */
    public function getGroupsList()
    {
        $this->setAction(self::ACTION_USR_GROUPS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getGroupsGrid();
        $Grid->getData()->setData(GroupSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de perfiles
     *
     * @throws \InvalidArgumentException
     */
    public function getProfilesList()
    {
        $this->setAction(self::ACTION_USR_PROFILES);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getProfilesGrid();
        $Grid->getData()->setData(ProfileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \InvalidArgumentException
     */
    public function getAPITokensList()
    {
        $this->setAction(self::ACTION_MGM_APITOKENS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getTokensGrid();
        $Grid->getData()->setData(ApiTokensUtil::getTokensMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \InvalidArgumentException
     */
    public function getPublicLinksList()
    {
        if (!Checks::publicLinksIsEnabled()) {
            return;
        }

        $this->setAction(self::ACTION_MGM_PUBLICLINKS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \InvalidArgumentException
     */
    public function getPluginsList()
    {
        $this->setAction(self::ACTION_MGM_PLUGINS);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->Grids->getPluginsGrid();
        $Grid->getData()->setData(PluginSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }
}