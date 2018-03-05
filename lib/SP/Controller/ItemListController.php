<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\Account\AccountHistoryUtil;
use SP\Account\AccountUtil;
use SP\Controller\Grids\Items;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Mgmt\ApiTokens\ApiTokenSearch;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\CustomerSearch;
use SP\Mgmt\CustomFields\CustomFieldDefSearch;
use SP\Mgmt\Files\FileSearch;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Groups\GroupSearch;
use SP\Mgmt\Plugins\PluginSearch;
use SP\Mgmt\Profiles\ProfileSearch;
use SP\Mgmt\PublicLinks\PublicLinkSearch;
use SP\Mgmt\Tags\TagSearch;
use SP\Mgmt\Users\UserSearch;
use SP\Mvc\View\Template;

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
     * @param $template \SP\Mvc\View\Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $ItemSearchData = new ItemSearchData();
        $ItemSearchData->setLimitCount($this->configData->getAccountCount());
        $this->ItemSearchData = $ItemSearchData;
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACCOUNT_FILE);

        $this->view->addTemplate('files-list', 'account');

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', FileUtil::getAccountFiles($this->view->accountId));

        if (!is_array($this->view->templates) || count($this->view->templates) === 0) {
            return;
        }
    }

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        try {
            $this->useTabs();

            if ($type === self::TYPE_ACCOUNTS) {
                $this->getCategories();
                $this->getCustomers();
                $this->getCustomFields();
                $this->getFiles();
                $this->getAccounts();
                $this->getAccountsHistory();
                $this->getTags();
                $this->getPluginsList();

                $this->eventDispatcher->notifyEvent('show.itemlist.accounts', $this);
            } elseif ($type === self::TYPE_ACCESSES) {
                $this->getUsersList();
                $this->getGroupsList();
                $this->getProfilesList();
                $this->getAPITokensList();
                $this->getPublicLinksList();

                $this->eventDispatcher->notifyEvent('show.itemlist.accesses', $this);
            }
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para la pestaña de categorías
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getCategories()
    {
        $this->setAction(self::CATEGORY);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getCategoriesGrid();
        $Grid->getData()->setData(CategorySearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * @return Items
     */
    public function getGrids()
    {
        return $this->Grids;
    }

    /**
     * Obtener los datos para la pestaña de clientes
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getCustomers()
    {
        $this->setAction(self::CLIENT);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getCustomersGrid();
        $Grid->getData()->setData(CustomerSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de campos personalizados
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getCustomFields()
    {
        $this->setAction(self::CUSTOMFIELD);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDefSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de archivos
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getFiles()
    {
        if (!$this->configData->isFilesEnabled()) {
            return;
        }

        $this->setAction(self::FILE);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getFilesGrid();
        $Grid->getData()->setData(FileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de cuentas
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getAccounts()
    {
        $this->setAction(self::ACCOUNTMGR);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de cuentas en el histórico
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getAccountsHistory()
    {
        $this->setAction(self::ACCOUNTMGR_HISTORY);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getAccountsHistoryGrid();
        $Grid->getData()->setData(AccountHistoryUtil::getAccountsMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de etiquetas
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getTags()
    {
        $this->setAction(self::TAG);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getTagsGrid();
        $Grid->getData()->setData(TagSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getPluginsList()
    {
        $this->setAction(self::PLUGIN);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getPluginsGrid();
        $Grid->getData()->setData(PluginSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getUsersList()
    {
        $this->setAction(self::USER);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getUsersGrid();
        $Grid->getData()->setData(UserSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de grupos
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getGroupsList()
    {
        $this->setAction(self::GROUP);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getGroupsGrid();
        $Grid->getData()->setData(GroupSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de perfiles
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getProfilesList()
    {
        $this->setAction(self::PROFILE);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getProfilesGrid();
        $Grid->getData()->setData(ProfileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getAPITokensList()
    {
        $this->setAction(self::APITOKEN);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getTokensGrid();
        $Grid->getData()->setData(ApiTokenSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function getPublicLinksList()
    {
        if (!$this->configData->isPublinksEnabled()) {
            return;
        }

        $this->setAction(self::PUBLICLINK);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }
}