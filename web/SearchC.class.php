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

namespace Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de obtener los datos para presentar la búsqueda
 *
 * @package Controller
 */
class SearchC extends \SP_Controller implements ActionsInterface
{
    /**
     * Constantes de ordenación
     */
    const SORT_NAME = 1;
    const SORT_CATEGORY = 2;
    const SORT_USER = 3;
    const SORT_URL = 4;
    const SORT_CUSTOMER = 5;

    /**
     * Constructor
     *
     * @param $template \SP_Template con instancia de plantilla
     */
    public function __construct(\SP_Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('sk', \SP_Common::getSessionKey(true));
        $this->setVars();
    }

    /**
     * Establecer las variables necesarias para las plantillas
     */
    private function setVars()
    {
        $this->view->assign('isAdmin', ($_SESSION["uisadminapp"] || $_SESSION["uisadminacc"]));
        $this->view->assign('globalSearch', \SP_Config::getValue('globalsearch', 0));

        // Valores POST
        $this->view->assign('searchKey', \SP_Common::parseParams('p', 'skey', \SP_Common::parseParams('s', 'accountSearchKey', 0)));
        $this->view->assign('searchOrder', \SP_Common::parseParams('p', 'sorder', \SP_Common::parseParams('s', 'accountSearchOrder', 0)));
        $this->view->assign('searchCustomer', \SP_Common::parseParams('p', 'customer', \SP_Common::parseParams('s', 'accountSearchCustomer', 0)));
        $this->view->assign('searchCategory', \SP_Common::parseParams('p', 'category', \SP_Common::parseParams('s', 'accountSearchCategory', 0)));
        $this->view->assign('searchTxt', \SP_Common::parseParams('p', 'search', \SP_Common::parseParams('s', 'accountSearchTxt')));
        $this->view->assign('searchGlobal', \SP_Common::parseParams('p', 'gsearch', \SP_Common::parseParams('s', 'accountGlobalSearch', 0), false, 1));
        $this->view->assign('limitStart', \SP_Common::parseParams('p', 'start', \SP_Common::parseParams('s', 'accountSearchStart', 0)));
        $this->view->assign('limitCount', \SP_Common::parseParams('p', 'rpp', \SP_Common::parseParams('s', 'accountSearchLimit', \SP_Config::getValue('account_count', 10))));
    }

    /**
     * Obtener los datos para la caja de búsqueda
     */
    public function getSearchBox()
    {
        $this->view->addTemplate('searchbox');

        $this->view->assign('customersSelProp',
            array("name" => "customer",
                "id" => "selCustomer",
                "class" => "select-box",
                "size" => 1,
                "label" => "",
                "selected" => $this->view->searchCustomer,
                "default" => "",
                "js" => 'OnChange="clearSearch(1); accSearch(0)"',
                "attribs" => "")
        );

        $this->view->assign('categoriesSelProp',
            array("name" => "category",
                "id" => "selCategory",
                "class" => "select-box",
                "size" => 1,
                "label" => "",
                "selected" => $this->view->searchCategory,
                "default" => "",
                "js" => 'OnChange="clearSearch(1); accSearch(0)"',
                "attribs" => "")
        );
    }

    public function getSearch()
    {
        $this->view->addTemplate('search');

        $this->view->assign('queryTimeStart', microtime());

        $searchFilter = array(
            'txtSearch' => $this->view->searchTxt,
            'userId' => \SP_Common::parseParams('s', 'uid', 0),
            'groupId' => \SP_Common::parseParams('s', 'ugroup', 0),
            'categoryId' => $this->view->searchCategory,
            'customerId' => $this->view->searchCustomer,
            'keyId' => $this->view->searchKey,
            'txtOrder' => $this->view->searchOrder,
            'limitStart' => $this->view->limitStart,
            'limitCount' => $this->view->limitCount,
            'globalSearch' => $this->view->globalSearch
        );

        $resQuery = \SP_Accounts::getAccounts($searchFilter);

        if (!$resQuery) {
            $this->view->assign('accounts', false);
            return;
        }

        $this->processSearchResults($resQuery);
    }

    private function processSearchResults(&$results)
    {
        // Variables para la barra de navegación
        $this->view->assign('firstPage', ceil(($this->view->limitStart + 1) / $this->view->limitCount));
        $this->view->assign('lastPage', ceil(\SP_Accounts::$queryNumRows / $this->view->limitCount));
        $this->view->assign('totalRows', \SP_Accounts::$queryNumRows);
        $this->view->assign('limitLast', (($this->view->totalRows % $this->view->limitCount) == 0) ? $this->view->totalRows - $this->view->limitCount : floor($this->view->totalRows / $this->view->limitCount) * $this->view->limitCount);
        $this->view->assign('filterOn', ($this->view->searchKey > 1 || $this->view->searchCustomer || $this->view->searchCategory || $this->view->searchTxt) ? true : false);

        // Variables de configuración
        $this->view->assign('accountLink', \SP_Config::getValue('account_link', 0));
        $this->view->assign('requestEnabled', \SP_Util::mailrequestIsEnabled());
        $this->view->assign('isDemoMode', \SP_Util::demoIsEnabled());
        $maxTextLength = (\SP_Util::resultsCardsIsEnabled()) ? 40 : 60;

        $wikiEnabled = \SP_Util::wikiIsEnabled();

        if ($wikiEnabled) {
            $wikiSearchUrl = \SP_Config::getValue('wiki_searchurl', false);
            $this->view->assign('wikiFilter', explode(',', \SP_Config::getValue('wiki_filter')));
            $this->view->assign('wikiPageUrl', \SP_Config::getValue('wiki_pageurl'));
        }

        $colors = array(
            'ef5350',
            'ec407a',
            'ab47bc',
            '7e57c2',
            '5c6bc0',
            '42a5f5',
            '29b6f6',
            '26c6da',
            '26a69a',
            '66bb6a',
            '9ccc65',
            'ff7043',
            '8d6e63',
            '78909c'
        );

        $this->setSortFields();

        $objAccount = new \SP_Accounts();

        foreach ($results as $account) {
            $objAccount->accountId = $account->account_id;
            $objAccount->accountUserId = $account->account_userId;
            $objAccount->accountUserGroupId = $account->account_userGroupId;
            $objAccount->accountOtherUserEdit = $account->account_otherUserEdit;
            $objAccount->accountOtherGroupEdit = $account->account_otherGroupEdit;

            // Obtener los datos de la cuenta para aplicar las ACL
            $accountAclData = $objAccount->getAccountDataForACL();

            // Establecer los permisos de acceso
            $accView = (\SP_Acl::checkAccountAccess(self::ACTION_ACC_VIEW, $accountAclData) && \SP_Acl::checkUserAccess(self::ACTION_ACC_VIEW));
            $accViewPass = (\SP_Acl::checkAccountAccess(self::ACTION_ACC_VIEW_PASS, $accountAclData) && \SP_Acl::checkUserAccess(self::ACTION_ACC_VIEW_PASS));
            $accEdit = (\SP_Acl::checkAccountAccess(self::ACTION_ACC_EDIT, $accountAclData) && \SP_Acl::checkUserAccess(self::ACTION_ACC_EDIT));
            $accCopy = (\SP_Acl::checkAccountAccess(self::ACTION_ACC_COPY, $accountAclData) && \SP_Acl::checkUserAccess(self::ACTION_ACC_COPY));
            $accDel = (\SP_Acl::checkAccountAccess(self::ACTION_ACC_DELETE, $accountAclData) && \SP_Acl::checkUserAccess(self::ACTION_ACC_DELETE));

            $show = ($accView || $accViewPass || $accEdit || $accCopy || $accDel);

            // Se asigna el color de forma aleatoria a cada cliente
            $color = array_rand($colors);

            if (!isset($customerColor) || !array_key_exists($account->account_customerId, $customerColor)) {
                $customerColor[$account->account_customerId] = '#' . $colors[$color];
            }

            $hexColor = $customerColor[$account->account_customerId];

            // Obtenemos datos si el usuario tiene acceso a los datos de la cuenta
            if ($show) {
                $secondaryGroups = \SP_Groups::getGroupsNameForAccount($account->account_id);
                $secondaryUsers = \SP_Users::getUsersNameForAccount($account->account_id);

                $secondaryAccesses = '<em>(G) ' . $account->usergroup_name . '*</em><br>';

                if ($secondaryGroups) {
                    foreach ($secondaryGroups as $group) {
                        $secondaryAccesses .= '<em>(G) ' . $group . '</em><br>';
                    }
                }

                if ($secondaryUsers) {
                    foreach ($secondaryUsers as $user) {
                        $secondaryAccesses .= '<em>(U) ' . $user . '</em><br>';
                    }
                }

                if ($account->account_notes) {
                    $accountNotes = (strlen($account->account_notes) > 300) ? substr($account->account_notes, 0, 300) . "..." : $account->account_notes;
                    $accountNotes = nl2br(wordwrap(htmlspecialchars($accountNotes), 50, '<br>', true));
                }
            }

            // Variable de la plantilla utilizada para obtener los datos de las cuentas
            $this->view->append('accounts', array(
                'id' => $account->account_id,
                'name' => $account->account_name,
                'login' => \SP_Html::truncate($account->account_login, $maxTextLength),
                'category_name' => $account->category_name,
                'customer_name' => \SP_Html::truncate($account->customer_name, $maxTextLength),
                'customer_link' => ($wikiEnabled) ? $wikiSearchUrl . $account->customer_name : '',
                'color' => $hexColor,
                'url' => $account->account_url,
                'url_short' => \SP_Html::truncate($account->account_url, $maxTextLength),
                'url_islink' => (preg_match("#^https?://.*#i", $account->account_url)) ? true : false,
                'notes' => (isset($accountNotes)) ? $accountNotes : '',
                'accesses' => (isset($secondaryAccesses)) ? $secondaryAccesses : '',
                'numFiles' => (\SP_Util::fileIsEnabled()) ? \SP_Files::countFiles($account->account_id) : 0,
                'show' => $show,
                'showView' => $accView,
                'showViewPass' => $accViewPass,
                'showEdit' => $accEdit,
                'showCopy' => $accCopy,
                'showDel' => $accDel,
            ));
        }
    }

    /**
     * Establecer los campos de ordenación
     */
    private function setSortFields()
    {
        $this->view->assign('sortFields', array(
            array('key' => self::SORT_CUSTOMER, 'title' => _('Ordenar por Cliente'), 'name' => _('Cliente'), 'function' => 'searchSort('. self::SORT_CUSTOMER . ',' . $this->view->limitStart . ')'),
            array('key' => self::SORT_NAME, 'title' => _('Ordenar por Nombre'), 'name' => _('Nombre'), 'function' => 'searchSort('. self::SORT_NAME . ',' . $this->view->limitStart . ')'),
            array('key' => self::SORT_CATEGORY, 'title' => _('Ordenar por Categoría'), 'name' => _('Categoría'), 'function' => 'searchSort('. self::SORT_CATEGORY . ',' . $this->view->limitStart . ')'),
            array('key' => self::SORT_USER, 'title' => _('Ordenar por Usuario'), 'name' => _('Usuario'), 'function' => 'searchSort('. self::SORT_USER . ',' . $this->view->limitStart . ')'),
            array('key' => self::SORT_URL, 'title' => _('Ordenar por URL / IP'), 'name' => _('URL / IP'), 'function' => 'searchSort('. self::SORT_URL . ',' . $this->view->limitStart . ')')
        ));
    }
}