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

namespace SP\Account;

use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\DataModel\AccountData;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Mgmt\Groups\GroupUtil;
use SP\Storage\DB;
use SP\Mgmt\Groups\Group;
use SP\Html\Html;
use SP\Core\Session;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\QueryData;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class AccountSearch para la gestión de búsquedas de cuentas
 */
class AccountSearch
{
    /**
     * Constantes de ordenación
     */
    const SORT_NAME = 1;
    const SORT_CATEGORY = 2;
    const SORT_LOGIN = 3;
    const SORT_URL = 4;
    const SORT_CUSTOMER = 5;
    const SORT_DIR_ASC = 0;
    const SORT_DIR_DESC = 1;
    /**
     * @var int El número de registros de la última consulta
     */
    public static $queryNumRows;
    /**
     * Colores para resaltar las cuentas
     *
     * @var array
     */
    private $colors = array(
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
     * @var bool
     */
    private $globalSearch = false;
    /**
     * @var string
     */
    private $txtSearch = '';
    /**
     * @var int
     */
    private $customerId = 0;
    /**
     * @var int
     */
    private $categoryId = 0;
    /**
     * @var int
     */
    private $sortOrder = 0;
    /**
     * @var int
     */
    private $sortKey = 0;
    /**
     * @var int
     */
    private $limitStart = 0;
    /**
     * @var int
     */
    private $limitCount = 12;
    /**
     * @var bool
     */
    private $sortViews = false;
    /**
     * @var bool
     */
    private $searchFavorites = false;

    /**
     * Constructor
     */
    function __construct()
    {
        $userResultsPerPage = (Session::getSessionType() === Session::SESSION_INTERACTIVE) ? Session::getUserPreferences()->getResultsPerPage() : 0;

        $this->limitCount = ($userResultsPerPage > 0) ? $userResultsPerPage : Config::getConfig()->getAccountCount();
        $this->sortViews = (Session::getSessionType() === Session::SESSION_INTERACTIVE) ? Session::getUserPreferences()->isSortViews() : false;
    }

    /**
     * @return boolean
     */
    public function isSearchFavorites()
    {
        return $this->searchFavorites;
    }

    /**
     * @param boolean $searchFavorites
     */
    public function setSearchFavorites($searchFavorites)
    {
        $this->searchFavorites = (bool)$searchFavorites;
    }

    /**
     * @return int
     */
    public function getGlobalSearch()
    {
        return $this->globalSearch;
    }

    /**
     * @param int $globalSearch
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = $globalSearch;
    }

    /**
     * @return string
     */
    public function getTxtSearch()
    {
        return $this->txtSearch;
    }

    /**
     * @param string $txtSearch
     */
    public function setTxtSearch($txtSearch)
    {
        $this->txtSearch = (string)$txtSearch;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return int
     */
    public function getLimitStart()
    {
        return $this->limitStart;
    }

    /**
     * @param int $limitStart
     */
    public function setLimitStart($limitStart)
    {
        $this->limitStart = $limitStart;
    }

    /**
     * @return int
     */
    public function getLimitCount()
    {
        return $this->limitCount;
    }

    /**
     * @param int $limitCount
     */
    public function setLimitCount($limitCount)
    {
        $this->limitCount = $limitCount;
    }

    /**
     * Obtiene el número de cuentas que un usuario puede ver.
     *
     * @return false|int con el número de registros
     */
    public function getAccountMax()
    {
        $Data = new QueryData();

        if (!Session::getUserIsAdminApp() && !Session::getUserIsAdminAcc()) {
            $query = 'SELECT COUNT(DISTINCT account_id) as numacc '
                . 'FROM accounts '
                . 'LEFT JOIN accGroups ON account_id = accgroup_accountId '
                . 'WHERE account_userGroupId = :userGroupId '
                . 'OR account_userId = :userId '
                . 'OR accgroup_groupId = :groupId';

            $Data->addParam(Session::getUserGroupId(), 'userGroupId');
            $Data->addParam(Session::getUserGroupId(), 'groupId');
            $Data->addParam(Session::getUserId(), 'userId');
        } else {
            $query = "SELECT COUNT(*) as numacc FROM accounts";
        }

        $Data->setQuery($query);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->numacc;
    }

    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @return array
     */
    public function processSearchResults()
    {
        if (!$results = $this->getAccounts()) {
            return [];
        }

        // Variables de configuración
        $maxTextLength = (Checks::resultsCardsIsEnabled()) ? 40 : 60;

        $favorites = AccountFavorites::getFavorites(Session::getUserId());

        $Account = new Account(new AccountData());
        $accountsData['count'] = self::$queryNumRows;

        foreach ($results as $account) {
            $Account->getAccountData()->setAccountId($account->account_id);
            $Account->getAccountData()->setAccountUserId($account->account_userId);
            $Account->getAccountData()->setAccountUsersId($Account->getUsersAccount());
            $Account->getAccountData()->setAccountUserGroupId($account->account_userGroupId);
            $Account->getAccountData()->setAccountUserGroupsId($Account->getGroupsAccount());
            $Account->getAccountData()->setAccountOtherUserEdit($account->account_otherUserEdit);
            $Account->getAccountData()->setAccountOtherGroupEdit($account->account_otherGroupEdit);

            // Obtener los datos de la cuenta para aplicar las ACL
            $accountAclData = $Account->getAccountDataForACL();

            $AccountSearchData = new AccountsSearchData();
            $AccountSearchData->setTextMaxLength($maxTextLength);
            $AccountSearchData->setId($account->account_id);
            $AccountSearchData->setName($account->account_name);
            $AccountSearchData->setLogin($account->account_login);
            $AccountSearchData->setCategoryName($account->category_name);
            $AccountSearchData->setCustomerName($account->customer_name);
            $AccountSearchData->setCustomerLink((AccountsSearchData::$wikiEnabled) ? Config::getConfig()->getWikiSearchurl() . $account->customer_name : '');
            $AccountSearchData->setColor($this->pickAccountColor($account->account_customerId));
            $AccountSearchData->setUrl($account->account_url);
            $AccountSearchData->setFavorite(in_array($account->account_id, $favorites));
            $AccountSearchData->setTags(AccountTags::getTags($Account->getAccountData()));
            $AccountSearchData->setNumFiles((Checks::fileIsEnabled()) ? $account->num_files : 0);
            $AccountSearchData->setShowView(Acl::checkAccountAccess(ActionsInterface::ACTION_ACC_VIEW, $accountAclData) && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW));
            $AccountSearchData->setShowViewPass(Acl::checkAccountAccess(ActionsInterface::ACTION_ACC_VIEW_PASS, $accountAclData) && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW_PASS));
            $AccountSearchData->setShowEdit(Acl::checkAccountAccess(ActionsInterface::ACTION_ACC_EDIT, $accountAclData) && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_EDIT));
            $AccountSearchData->setShowCopy(Acl::checkAccountAccess(ActionsInterface::ACTION_ACC_COPY, $accountAclData) && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_COPY));
            $AccountSearchData->setShowDelete(Acl::checkAccountAccess(ActionsInterface::ACTION_ACC_DELETE, $accountAclData) && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_DELETE));

            // Obtenemos datos si el usuario tiene acceso a los datos de la cuenta
            if ($AccountSearchData->isShow()) {
                $secondaryAccesses = sprintf('<em>(G) %s*</em><br>', $account->usergroup_name);

                foreach (GroupAccountsUtil::getGroupsInfoForAccount($account->account_id) as $group) {
                    $secondaryAccesses .= sprintf('<em>(G) %s</em><br>', $group->getUsergroupName());
                }

                foreach (UserAccounts::getUsersInfoForAccount($account->account_id) as $user) {
                    $secondaryAccesses .= sprintf('<em>(U) %s</em><br>', $user->getUserLogin());
                }

                $AccountSearchData->setAccesses($secondaryAccesses);

                $accountNotes = '';

                if ($account->account_notes) {
                    $accountNotes = (strlen($account->account_notes) > 300) ? substr($account->account_notes, 0, 300) . "..." : $account->account_notes;
                    $accountNotes = nl2br(wordwrap(htmlspecialchars($accountNotes), 50, '<br>', true));
                }

                $AccountSearchData->setNotes($accountNotes);
            }

            $accountsData[] = $AccountSearchData;
        }

        return $accountsData;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @return mixed Resultado de la consulta
     */
    public function getAccounts()
    {
        $isAdmin = (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc());

        $arrFilterCommon = [];
        $arrFilterSelect = [];
        $arrFilterUser = [];
        $arrQueryWhere = [];
        $queryLimit = '';

        $Data = new QueryData();

        if ($this->txtSearch) {
            // Analizar la cadena de búsqueda por etiquetas especiales
            $stringFilters = $this->analyzeQueryString();

            if ($stringFilters !== false) {
                foreach ($stringFilters as $column => $value) {
                    $rel = '=';

                    if (preg_match('/name/i', $column)) {
                        $rel = 'LIKE';
                        $value = '%' . $value . '%';
                    }

                    $arrFilterCommon[] = $column . ' ' . $rel . ' ?';

                    $Data->addParam($value);
                }
            } else {
                $txtSearch = '%' . $this->txtSearch . '%';

                $arrFilterCommon[] = 'account_name LIKE ?';
                $Data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_login LIKE ?';
                $Data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_url LIKE ?';
                $Data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_notes LIKE ?';
                $Data->addParam($txtSearch);

            }
        }

        if ($this->categoryId !== 0) {
            $arrFilterSelect[] = 'category_id = ?';

            $Data->addParam($this->categoryId);
        }

        if ($this->customerId !== 0) {
            $arrFilterSelect[] = 'account_customerId = ?';

            $Data->addParam($this->customerId);
        }

        if ($this->searchFavorites === true) {
            $arrFilterSelect[] = 'accFavorites.accfavorite_userId = ?';

            $Data->addParam(Session::getUserId());
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        if (!$isAdmin && !$this->globalSearch) {
            $subQueryGroups = '(SELECT user_groupId FROM usrData WHERE user_id = ? UNION ALL SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_userId = ?)';

            // Buscar el grupo principal de la cuenta en los grupos del usuario
            $arrFilterUser[] = 'account_userGroupId IN ' . $subQueryGroups;
            $Data->addParam(Session::getUserId());
            $Data->addParam(Session::getUserId());

            // Buscar los grupos secundarios de la cuenta en los grupos del usuario
            $arrFilterUser[] = 'accgroup_groupId IN ' . $subQueryGroups;
            $Data->addParam(Session::getUserId());
            $Data->addParam(Session::getUserId());

            // Comprobar el usuario principal de la cuenta con el usuario actual
            $arrFilterUser[] = 'account_userId = ?';
            $Data->addParam(Session::getUserId());

            // Comprobar los usuarios secundarios de la cuenta con el usuario actual
            $arrFilterUser[] = 'accuser_userId = ?';
            $Data->addParam(Session::getUserId());

            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterUser) . ')';
        }

        if ($this->limitCount > 0) {
            $queryLimit = 'LIMIT ?, ?';

            $Data->addParam($this->limitStart);
            $Data->addParam($this->limitCount);
        }

        if (count($arrQueryWhere) === 1) {
            $queryWhere = ' WHERE ' . implode($arrQueryWhere);
        } elseif (count($arrQueryWhere) > 1) {
            $queryWhere = ' WHERE ' . implode(' AND ', $arrQueryWhere);
        } else {
            $queryWhere = '';
        }

        $query = 'SELECT DISTINCT account_id,
            account_customerId,
            category_name,
            account_name,
            account_login,
            account_url,
            account_notes,
            account_userId,
            account_userGroupId,
            BIN(account_otherUserEdit) AS account_otherUserEdit,
            BIN(account_otherGroupEdit) AS account_otherGroupEdit,
            usergroup_name,
            customer_name,
            count(accfile_id) as num_files
            FROM accounts
            LEFT JOIN accFiles ON account_id = accfile_accountId
            LEFT JOIN categories ON account_categoryId = category_id
            LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id
            LEFT JOIN customers ON customer_id = account_customerId
            LEFT JOIN accUsers ON accuser_accountId = account_id
            LEFT JOIN accGroups ON accgroup_accountId = account_id
            LEFT JOIN accFavorites ON accfavorite_accountId = account_id
            LEFT JOIN accTags ON acctag_accountId = account_id
            LEFT JOIN tags ON tag_id = acctag_tagId
            ' . $queryWhere . '
            GROUP BY account_id
            ' . $this->getOrderString() . ' ' . $queryLimit;

        $Data->setQuery($query);

        // Obtener el número total de cuentas visibles por el usuario
        DB::setFullRowCount();

        // Obtener los resultados siempre en array de objetos
        DB::setReturnArray();

        // Consulta de la búsqueda de cuentas
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        self::$queryNumRows = DB::$lastNumRows;

        // Establecer el filtro de búsqueda en la sesión como un objeto
        Session::setSearchFilters($this);

        return $queryRes;
    }

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un array
     * con las columnas y los valores a buscar.
     *
     * @return array|bool
     */
    private function analyzeQueryString()
    {
        preg_match('/(user|group|file|tag):(.*)/i', $this->txtSearch, $filters);

        if (!is_array($filters) || count($filters) === 0) {
            return false;
        }

        switch ($filters[1]) {
            case 'user':
                $UserData = UserUtil::getUserIdByLogin(Html::sanitize($filters[2]));
                return [
                    'account_userId' => $UserData,
                    'accuser_userId' => $UserData
                ];
                break;
            case 'group':
                $GroupData = GroupUtil::getGroupIdByName(Html::sanitize($filters[2]));
                return [
                    'account_userGroupId' => $GroupData->getUsergroupId(),
                    'accgroup_groupId' => $GroupData->getUsergroupId()
                ];
                break;
            case 'file':
                return [
                    'accfile_name' => Html::sanitize($filters[2])
                ];
                break;
            case 'tag':
                return [
                    'tag_name' => Html::sanitize($filters[2])
                ];
                break;
            default:
                return false;
        }
    }

    /**
     * Devuelve la cadena de ordenación de la consulta
     *
     * @return string
     */
    private function getOrderString()
    {
        switch ($this->sortKey) {
            case self::SORT_NAME:
                $orderKey[] = 'account_name';
                break;
            case self::SORT_CATEGORY:
                $orderKey[] = 'category_name';
                break;
            case self::SORT_LOGIN:
                $orderKey[] = 'account_login';
                break;
            case self::SORT_URL:
                $orderKey[] = 'account_url';
                break;
            case self::SORT_CUSTOMER:
                $orderKey[] = 'customer_name';
                break;
            default :
                $orderKey[] = 'customer_name';
                $orderKey[] = 'account_name';
                break;
        }

        if ($this->isSortViews() && !$this->getSortKey()) {
            array_unshift($orderKey, 'account_countView DESC');
            $this->setSortOrder(self::SORT_DIR_DESC);
        }

        $orderDir = ($this->sortOrder === self::SORT_DIR_ASC) ? 'ASC' : 'DESC';
        return sprintf('ORDER BY %s %s', implode(',', $orderKey), $orderDir);
    }

    /**
     * @return boolean
     */
    public function isSortViews()
    {
        return $this->sortViews;
    }

    /**
     * @param boolean $sortViews
     */
    public function setSortViews($sortViews)
    {
        $this->sortViews = $sortViews;
    }

    /**
     * @return int
     */
    public function getSortKey()
    {
        return $this->sortKey;
    }

    /**
     * @param int $sortKey
     */
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;
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
            $color = array_rand($this->colors);

            $accountColor[$id] = '#' . $this->colors[$color];
            Session::setAccountColor($accountColor);
        }

        return $accountColor[$id];
    }
}