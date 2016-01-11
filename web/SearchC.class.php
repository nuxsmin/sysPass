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

use SP\Session;
use SP\SessionUtil;
use SP\UserAccounts;
use SP\UserUtil;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de obtener los datos para presentar la búsqueda
 *
 * @package Controller
 */
class SearchC extends Controller implements ActionsInterface
{
    /**
     * Indica si el filtrado de cuentas está activo
     *
     * @var bool
     */
    private $_filterOn = false;
    /**
     * Colores para resaltar las cuentas
     *
     * @var array
     */
    private $_colors = array(
        '2196F3',
        '03A9F4',
        '00BCD4',
        '009688',
        '4CAF50',
        '8BC34A',
        'CDDC39',
        'FFC107',
        '795548',
        '607D8B',
        '9E9E9E',
        'FF5722',
        'F44336',
        'E91E63',
        '9C27B0',
        '673AB7',
        '3F51B5',
    );

    /**
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->setVars();
    }

    /**
     * Establecer las variables necesarias para las plantillas
     */
    private function setVars()
    {
        $this->view->assign('isAdmin', (\SP\Session::getUserIsAdminApp() || \SP\Session::getUserIsAdminAcc()));
        $this->view->assign('showGlobalSearch', \SP\Config::getValue('globalsearch', false));

        // Comprobar si está creado el objeto de búsqueda en la sesión
        if (!is_object(\SP\Session::getSearchFilters())) {
            \SP\Session::setSearchFilters(new \SP\AccountSearch());
        }

        // Obtener el filtro de búsqueda desde la sesión
        $filters = \SP\Session::getSearchFilters();

        // Comprobar si la búsqueda es realizada desde el fromulario
        // de lo contrario, se recupera la información de filtros de la sesión
        $isSearch = (!isset($this->view->actionId));

        // Valores POST
        $this->view->assign('searchKey', ($isSearch) ? \SP\Request::analyze('skey', 0) : $filters->getSortKey());
        $this->view->assign('searchOrder', ($isSearch) ? \SP\Request::analyze('sorder', 0) : $filters->getSortOrder());
        $this->view->assign('searchCustomer', ($isSearch) ? \SP\Request::analyze('customer', 0) : $filters->getCustomerId());
        $this->view->assign('searchCategory', ($isSearch) ? \SP\Request::analyze('category', 0 ) : $filters->getCategoryId());
        $this->view->assign('searchTxt', ($isSearch) ? \SP\Request::analyze('search') : $filters->getTxtSearch());
        $this->view->assign('searchGlobal', \SP\Request::analyze('gsearch', $filters->getGlobalSearch()));
        $this->view->assign('limitStart', ($isSearch) ? \SP\Request::analyze('start', 0) : $filters->getLimitStart());
        $this->view->assign('limitCount', ($isSearch) ? \SP\Request::analyze('rpp', 0) : $filters->getLimitCount());
    }

    /**
     * Obtener los datos para la caja de búsqueda
     */
    public function getSearchBox()
    {
        $this->view->addTemplate('searchbox');

        $this->view->assign('customers', \SP\DB::getValuesForSelect('customers', 'customer_id', 'customer_name'));
        $this->view->assign('categories', \SP\DB::getValuesForSelect('categories', 'category_id', 'category_name'));
    }

    /**
     * Obtener los resultados de una búsqueda
     */
    public function getSearch()
    {
        $this->view->addTemplate('search');

        $this->view->assign('queryTimeStart', microtime());

        $search = new \SP\AccountSearch();

        $search->setGlobalSearch($this->view->searchGlobal);
        $search->setTxtSearch($this->view->searchTxt);
        $search->setCategoryId($this->view->searchCategory);
        $search->setCustomerId($this->view->searchCustomer);
        $search->setSortKey($this->view->searchKey);
        $search->setSortOrder($this->view->searchOrder);
        $search->setLimitStart($this->view->limitStart);
        $search->setLimitCount($this->view->limitCount);

        $resQuery = $search->getAccounts();

        $this->_filterOn = ($this->view->searchKey > 1
            || $this->view->searchCustomer
            || $this->view->searchCategory
            || $this->view->searchTxt
            || $search->isSortViews());

        if (!$resQuery) {
            $this->view->assign('accounts', false);
            return;
        }

        $this->processSearchResults($resQuery);
    }

    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @param &$results array Con los resultados de la búsqueda
     */
    private function processSearchResults(&$results)
    {
        // Variables para la barra de navegación
        $this->view->assign('firstPage', ceil(($this->view->limitStart + 1) / $this->view->limitCount));
        $this->view->assign('lastPage', ceil(\SP\AccountSearch::$queryNumRows / $this->view->limitCount));
        $this->view->assign('totalRows', \SP\AccountSearch::$queryNumRows);
        $this->view->assign('filterOn', $this->_filterOn);

        $limitLast = ((\SP\AccountSearch::$queryNumRows % $this->view->limitCount) == 0) ? \SP\AccountSearch::$queryNumRows - $this->view->limitCount : floor(\SP\AccountSearch::$queryNumRows / $this->view->limitCount) * $this->view->limitCount;

        $this->view->assign('pagerOnnClick', array(
            'first' => 'sysPassUtil.Common.searchSort(' . $this->view->searchKey . ', 0,1)',
            'last' => 'sysPassUtil.Common.searchSort(' . $this->view->searchKey . ',' . $limitLast . ',1)',
            'prev' => 'sysPassUtil.Common.searchSort(' . $this->view->searchKey . ',' . ($this->view->limitStart - $this->view->limitCount) . ',1)',
            'next' => 'sysPassUtil.Common.searchSort(' . $this->view->searchKey . ',' . ($this->view->limitStart + $this->view->limitCount) . ',1)',
        ));

        $accountLink = Session::getUserPreferences()->isAccountLink();
        $topNavbar = Session::getUserPreferences()->isTopNavbar();
        $optionalActions = Session::getUserPreferences()->isOptionalActions();

        // Variables de configuración
        $this->view->assign('accountLink', (is_null($accountLink) ? \SP\Config::getValue('account_link', 0) : $accountLink));
        $this->view->assign('topNavbar', $topNavbar);
        $this->view->assign('optionalActions', $optionalActions);
        $this->view->assign('requestEnabled', \SP\Util::mailrequestIsEnabled());
        $this->view->assign('isDemoMode', \SP\Util::demoIsEnabled());
        $maxTextLength = (\SP\Util::resultsCardsIsEnabled()) ? 40 : 60;

        $wikiEnabled = \SP\Util::wikiIsEnabled();

        if ($wikiEnabled) {
            $wikiSearchUrl = \SP\Config::getValue('wiki_searchurl', false);
            $this->view->assign('wikiFilter', explode(',', \SP\Config::getValue('wiki_filter')));
            $this->view->assign('wikiPageUrl', \SP\Config::getValue('wiki_pageurl'));
        }

        $this->setSortFields();

        $objAccount = new \SP\Account();

        foreach ($results as $account) {
            $objAccount->setAccountId($account->account_id);
            $objAccount->setAccountUserId($account->account_userId);
            $objAccount->setAccountUserGroupId($account->account_userGroupId);
            $objAccount->setAccountOtherUserEdit($account->account_otherUserEdit);
            $objAccount->setAccountOtherGroupEdit($account->account_otherGroupEdit);

            // Obtener los datos de la cuenta para aplicar las ACL
            $accountAclData = $objAccount->getAccountDataForACL();

            // Establecer los permisos de acceso
            $accView = (\SP\Acl::checkAccountAccess(self::ACTION_ACC_VIEW, $accountAclData) && \SP\Acl::checkUserAccess(self::ACTION_ACC_VIEW));
            $accViewPass = (\SP\Acl::checkAccountAccess(self::ACTION_ACC_VIEW_PASS, $accountAclData) && \SP\Acl::checkUserAccess(self::ACTION_ACC_VIEW_PASS));
            $accEdit = (\SP\Acl::checkAccountAccess(self::ACTION_ACC_EDIT, $accountAclData) && \SP\Acl::checkUserAccess(self::ACTION_ACC_EDIT));
            $accCopy = (\SP\Acl::checkAccountAccess(self::ACTION_ACC_COPY, $accountAclData) && \SP\Acl::checkUserAccess(self::ACTION_ACC_COPY));
            $accDel = (\SP\Acl::checkAccountAccess(self::ACTION_ACC_DELETE, $accountAclData) && \SP\Acl::checkUserAccess(self::ACTION_ACC_DELETE));

            $show = ($accView || $accViewPass || $accEdit || $accCopy || $accDel);

            // Obtenemos datos si el usuario tiene acceso a los datos de la cuenta
            if ($show) {
                $secondaryGroups = \SP\Groups::getGroupsNameForAccount($account->account_id);
                $secondaryUsers = UserAccounts::getUsersNameForAccount($account->account_id);

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

                $accountNotes = '';

                if ($account->account_notes) {
                    $accountNotes = (strlen($account->account_notes) > 300) ? substr($account->account_notes, 0, 300) . "..." : $account->account_notes;
                    $accountNotes = nl2br(wordwrap(htmlspecialchars($accountNotes), 50, '<br>', true));
                }
            }

            // Variable $accounts de la plantilla utilizada para obtener los datos de las cuentas
            $this->view->append('accounts', array(
                'id' => $account->account_id,
                'name' => $account->account_name,
                'login' => \SP\Html::truncate($account->account_login, $maxTextLength),
                'category_name' => $account->category_name,
                'customer_name' => \SP\Html::truncate($account->customer_name, $maxTextLength),
                'customer_link' => ($wikiEnabled) ? $wikiSearchUrl . $account->customer_name : '',
                'color' => $this->pickAccountColor($account->account_customerId),
                'url' => $account->account_url,
                'url_short' => \SP\Html::truncate($account->account_url, $maxTextLength),
                'url_islink' => (preg_match("#^https?://.*#i", $account->account_url)) ? true : false,
                'notes' => $accountNotes,
                'accesses' => (isset($secondaryAccesses)) ? $secondaryAccesses : '',
                'numFiles' => (\SP\Util::fileIsEnabled()) ? $account->num_files : 0,
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
            array(
                'key' => \SP\AccountSearch::SORT_CUSTOMER,
                'title' => _('Ordenar por Cliente'),
                'name' => _('Cliente'),
                'function' => 'sysPassUtil.Common.searchSort(' . \SP\AccountSearch::SORT_CUSTOMER . ',' . $this->view->limitStart . ')'
            ),
            array(
                'key' => \SP\AccountSearch::SORT_NAME,
                'title' => _('Ordenar por Nombre'),
                'name' => _('Nombre'),
                'function' => 'sysPassUtil.Common.searchSort(' . \SP\AccountSearch::SORT_NAME . ',' . $this->view->limitStart . ')'
            ),
            array(
                'key' => \SP\AccountSearch::SORT_CATEGORY,
                'title' => _('Ordenar por Categoría'),
                'name' => _('Categoría'),
                'function' => 'sysPassUtil.Common.searchSort(' . \SP\AccountSearch::SORT_CATEGORY . ',' . $this->view->limitStart . ')'
            ),
            array(
                'key' => \SP\AccountSearch::SORT_LOGIN,
                'title' => _('Ordenar por Usuario'),
                'name' => _('Usuario'),
                'function' => 'sysPassUtil.Common.searchSort(' . \SP\AccountSearch::SORT_LOGIN . ',' . $this->view->limitStart . ')'
            ),
            array(
                'key' => \SP\AccountSearch::SORT_URL,
                'title' => _('Ordenar por URL / IP'),
                'name' => _('URL / IP'),
                'function' => 'sysPassUtil.Common.searchSort(' . \SP\AccountSearch::SORT_URL . ',' . $this->view->limitStart . ')'
            )
        ));
    }

    /**
     * Seleccionar un color para la cuenta
     *
     * @param int $id El id del elemento a asignar
     * @return mixed
     */
    private function pickAccountColor($id)
    {
        $accountColor = Session::getAccountColor();

        if (!isset($accountColor)
            || !is_array($accountColor)
            || !isset($accountColor[$id])
        ) {
            // Se asigna el color de forma aleatoria a cada id
            $color = array_rand($this->_colors);

            $accountColor[$id] = '#' . $this->_colors[$color];
            Session::setAccountColor($accountColor);
        }

        return $accountColor[$id];
    }
}