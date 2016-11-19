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
use SP\Api\ApiTokensUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\CustomerSearch;
use SP\Mgmt\CustomFields\CustomFieldDefSearch;
use SP\Mgmt\Files\FileSearch;
use SP\Mgmt\Groups\GroupSearch;
use SP\Mgmt\Profiles\ProfileSearch;
use SP\Mgmt\PublicLinks\PublicLinkSearch;
use SP\Mgmt\Tags\TagSearch;
use SP\Mgmt\Users\UserSearch;
use SP\Util\Checks;
use SP\Util\Json;

/**
 * Class AccItemsMgmtSearch para la gestión de búsquedas de items de accesos
 *
 * @package SP\Controller
 */
class ItemSearchController extends GridItemsSearchController implements ActionsInterface, ItemControllerInterface
{
    use RequestControllerTrait;

    /**
     * @var ItemSearchData
     */
    protected $ItemSearchData;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->ItemSearchData = new ItemSearchData();

        $this->init();
        $this->setItemSearchData();
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('index', $this->activeTab);

        try {
            switch ($this->actionId) {
                case ActionsInterface::ACTION_USR_USERS_SEARCH:
                    $this->getUsers();
                    break;
                case ActionsInterface::ACTION_USR_GROUPS_SEARCH:
                    $this->getGroups();
                    break;
                case ActionsInterface::ACTION_USR_PROFILES_SEARCH:
                    $this->getProfiles();
                    break;
                case ActionsInterface::ACTION_MGM_APITOKENS_SEARCH:
                    $this->getTokens();
                    break;
                case ActionsInterface::ACTION_MGM_PUBLICLINKS_SEARCH:
                    $this->getPublicLinks();
                    break;
                case ActionsInterface::ACTION_MGM_CATEGORIES_SEARCH:
                    $this->getCategories();
                    break;
                case ActionsInterface::ACTION_MGM_CUSTOMERS_SEARCH:
                    $this->getCustomers();
                    break;
                case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_SEARCH:
                    $this->getCustomFields();
                    break;
                case ActionsInterface::ACTION_MGM_FILES_SEARCH:
                    $this->getFiles();
                    break;
                case ActionsInterface::ACTION_MGM_ACCOUNTS_SEARCH:
                    $this->getAccounts();
                    break;
                case ActionsInterface::ACTION_MGM_TAGS_SEARCH:
                    $this->getTags();
                    break;
                default:
                    $this->invalidAction();
            }

            $this->jsonResponse->setCsrf($this->view->sk);
            $this->jsonResponse->setData(['html' => $this->render()]);
        } catch (\Exception $e) {
            $this->jsonResponse->setDescription($e->getMessage());
        }

        Json::returnJson($this->jsonResponse);
    }

    /**
     * Obtener los usuarios de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getUsers()
    {
        $this->setAction(self::ACTION_USR_USERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getUsersGrid();
        $Grid->getData()->setData(UserSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los grupos de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getGroups()
    {
        $this->setAction(self::ACTION_USR_GROUPS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getGroupsGrid();
        $Grid->getData()->setData(GroupSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los perfiles de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getProfiles()
    {
        $this->setAction(self::ACTION_USR_PROFILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getProfilesGrid();
        $Grid->getData()->setData(ProfileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los tokens API de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getTokens()
    {
        $this->setAction(self::ACTION_MGM_APITOKENS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getTokensGrid();
        $Grid->getData()->setData(ApiTokensUtil::getTokensMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los enlaces públicos de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getPublicLinks()
    {
        $this->setAction(self::ACTION_MGM_PUBLICLINKS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getPublicLinksGrid();
        $Grid->getData()->setData(PublicLinkSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_USR);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener las categorías de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getCategories()
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getCategoriesGrid();
        $Grid->getData()->setData(CategorySearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los clientes de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getCustomers()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getCustomersGrid();
        $Grid->getData()->setData(CustomerSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los campos personalizados de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getCustomFields()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getCustomFieldsGrid();
        $Grid->getData()->setData(CustomFieldDefSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener los archivos de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getFiles()
    {
        $this->setAction(self::ACTION_MGM_FILES_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getFilesGrid();
        $Grid->getData()->setData(FileSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener las cuentas de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getAccounts()
    {
        $this->setAction(self::ACTION_MGM_ACCOUNTS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getAccountsGrid();
        $Grid->getData()->setData(AccountUtil::getAccountsMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Obtener las etiquetas de una búsqueda
     *
     * @throws \InvalidArgumentException
     */
    public function getTags()
    {
        $this->setAction(self::ACTION_MGM_TAGS_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-rows', 'grid');

        $Grid = $this->grids->getTagsGrid();
        $Grid->getData()->setData(TagSearch::getItem()->getMgmtSearch($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_MGM);

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Establecer las propiedades de búsqueda
     */
    protected function setItemSearchData()
    {
        $this->ItemSearchData->setSeachString(Request::analyze('search'));
        $this->ItemSearchData->setLimitStart(Request::analyze('start', 0));
        $this->ItemSearchData->setLimitCount(Request::analyze('count', Config::getConfig()->getAccountCount()));
    }
}