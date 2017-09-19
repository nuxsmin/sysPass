<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Account;

use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\Session;
use SP\DataModel\AccountSearchData;
use SP\Mgmt\Groups\GroupUtil;
use SP\Mgmt\Users\User;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Filter;

defined('APP_ROOT') || die();

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
    private static $colors = [
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
    ];

    /**
     * @var bool
     */
    private $globalSearch = false;
    /**
     * @var string
     */
    private $txtSearch;
    /**
     * @var int
     */
    private $customerId = 0;
    /**
     * @var int
     */
    private $categoryId = 0;
    /**
     * @var array
     */
    private $tagsId = [];
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
    public function __construct()
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
     * @return $this
     */
    public function setSearchFavorites($searchFavorites)
    {
        $this->searchFavorites = (bool)$searchFavorites;

        return $this;
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
     * @return $this
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = $globalSearch;

        return $this;
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
     * @return $this
     */
    public function setTxtSearch($txtSearch)
    {
        $this->txtSearch = Filter::safeSearchString($txtSearch);

        return $this;
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
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
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
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
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
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
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
     * @return $this
     */
    public function setLimitStart($limitStart)
    {
        $this->limitStart = $limitStart;

        return $this;
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
     * @return $this
     */
    public function setLimitCount($limitCount)
    {
        $this->limitCount = $limitCount;

        return $this;
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
        $maxTextLength = Checks::resultsCardsIsEnabled() ? 40 : 60;

        $accountsData['count'] = self::$queryNumRows;

        $accountLinkEnabled = Session::getUserPreferences()->isAccountLink() || Config::getConfig()->isAccountLink();
        $favorites = AccountFavorites::getFavorites(Session::getUserData()->getUserId());

        foreach ($results as $AccountSearchData) {
            // Establecer los datos de la cuenta
            $Account = new Account($AccountSearchData);

            // Propiedades de búsqueda de cada cuenta
            $AccountSearchItems = new AccountsSearchItem($AccountSearchData);

            // Obtener la ACL de la cuenta
            $AccountAcl = new AccountAcl($Account, Acl::ACTION_ACC_SEARCH);

            if (!$AccountSearchData->getAccountIsPrivate()) {
                $AccountSearchData->setUsersId($AccountSearchItems->getCacheUsers(true));
                $AccountSearchData->setUserGroupsId($AccountSearchItems->getCacheGroups(true));
            }

            $AccountSearchData->setTags(AccountTags::getTags($Account->getAccountData()));

            // Obtener la ACL
            $Acl = $AccountAcl->getAcl();

            $AccountSearchItems->setTextMaxLength($maxTextLength);
            $AccountSearchItems->setColor($this->pickAccountColor($AccountSearchData->getAccountCustomerId()));
            $AccountSearchItems->setShowView($Acl->isShowView());
            $AccountSearchItems->setShowViewPass($Acl->isShowViewPass());
            $AccountSearchItems->setShowEdit($Acl->isShowEdit());
            $AccountSearchItems->setShowCopy($Acl->isShowCopy());
            $AccountSearchItems->setShowDelete($Acl->isShowDelete());
            $AccountSearchItems->setLink($accountLinkEnabled);
            $AccountSearchItems->setFavorite(in_array($AccountSearchData->getAccountId(), $favorites, true));

            $accountsData[] = $AccountSearchItems;
        }

        return $accountsData;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @return AccountSearchData[] Resultado de la consulta
     */
    public function getAccounts()
    {
        $arrFilterCommon = [];
        $arrFilterSelect = [];
        $arrayQueryJoin = [];
        $arrQueryWhere = [];
        $queryLimit = '';

        $Data = new QueryData();
        $Data->setMapClassName(AccountSearchData::class);

        if ($this->txtSearch !== null && $this->txtSearch !== '') {
            // Analizar la cadena de búsqueda por etiquetas especiales
            $stringFilters = $this->analyzeQueryString();

            if (count($stringFilters) > 0) {
                foreach ($stringFilters as $filter) {
                    $arrFilterCommon[] = $filter['query'];

                    foreach ($filter['values'] as $value) {
                        $Data->addParam($value);
                    }
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
            $arrFilterSelect[] = 'account_categoryId = ?';
            $Data->addParam($this->categoryId);
        }

        if ($this->customerId !== 0) {
            $arrFilterSelect[] = 'account_customerId = ?';
            $Data->addParam($this->customerId);
        }

        $numTags = count($this->tagsId);

        if ($numTags > 0) {
            $tags = str_repeat('?,', $numTags - 1) . '?';

            $arrFilterSelect[] = 'account_id IN (SELECT acctag_accountId FROM accTags WHERE acctag_tagId IN (' . $tags . '))';

            for ($i = 0; $i <= $numTags - 1; $i++) {
                $Data->addParam($this->tagsId[$i]);
            }
        }

        if ($this->searchFavorites === true) {
            $arrayQueryJoin[] = 'INNER JOIN accFavorites ON (accfavorite_accountId = account_id AND accfavorite_userId = ?)';
            $Data->addParam(Session::getUserData()->getUserId());
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        $arrQueryWhere = array_merge($arrQueryWhere, AccountUtil::getAccountFilterUser($Data, $this->globalSearch));

        if ($this->limitCount > 0) {
            $queryLimit = '?, ?';

            $Data->addParam($this->limitStart);
            $Data->addParam($this->limitCount);
        }

        $queryWhere = '';

        if (count($arrQueryWhere) === 1) {
            $queryWhere = implode($arrQueryWhere);
        } elseif (count($arrQueryWhere) > 1) {
            $queryWhere = implode(' AND ', $arrQueryWhere);
        }

        $queryJoin = implode('', $arrayQueryJoin);

        $Data->setSelect('*');
        $Data->setFrom('account_search_v ' . $queryJoin);
        $Data->setWhere($queryWhere);
        $Data->setOrder($this->getOrderString());
        $Data->setLimit($queryLimit);

        // Obtener el número total de cuentas visibles por el usuario
        DB::setFullRowCount();

//        Log::writeNewLog(__FUNCTION__, $Data->getQuery(), Log::DEBUG);
//        Log::writeNewLog(__FUNCTION__, print_r($Data->getParams(), true), Log::DEBUG);

        // Consulta de la búsqueda de cuentas
        $queryRes = DB::getResultsArray($Data);

        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        self::$queryNumRows = $Data->getQueryNumRows();

        // Establecer el filtro de búsqueda en la sesión como un objeto
        Session::setSearchFilters($this);

        return $queryRes;
    }

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un array
     * con las columnas y los valores a buscar.
     *
     * @return array|bool
     * @throws \SP\Core\Exceptions\SPException
     */
    private function analyzeQueryString()
    {
        if (!preg_match('/^(user|group|file|owner|maingroup):"([\w\.]+)"$/i', $this->txtSearch, $filters)
            && !preg_match('/^(expired|private):$/i', $this->txtSearch, $filters)
        ) {
            return [];
        }

        $filtersData = [];

        switch ($filters[1]) {
            case 'user':
                $UserData = User::getItem()->getByLogin($filters[2]);

                if (!is_object($UserData)) {
                    return [];
                }

                $filtersData[] = [
                    'type' => 'user',
                    'query' => 'account_userId = ? OR account_id IN (SELECT accuser_accountId AS accountId FROM accUsers WHERE accuser_accountId = account_id AND accuser_userId = ? UNION ALL SELECT accgroup_accountId AS accountId FROM accGroups WHERE accgroup_accountId = account_id AND accgroup_groupId = ?)',
                    'values' => [$UserData->getUserId(), $UserData->getUserId(), $UserData->getUserGroupId()]
                ];
                break;
            case 'owner':
                $UserData = User::getItem()->getByLogin($filters[2]);

                if (!is_object($UserData)) {
                    return [];
                }

                $filtersData[] = [
                    'type' => 'user',
                    'query' => 'account_userId = ?',
                    'values' => [$UserData->getUserId()]
                ];
                break;
            case 'group':
                $GroupData = GroupUtil::getGroupIdByName($filters[2]);

                if (!is_object($GroupData)) {
                    return [];
                }

                $filtersData[] = [
                    'type' => 'group',
                    'query' => 'account_userGroupId = ? OR account_id IN (SELECT accgroup_accountId AS accountId FROM accGroups WHERE accgroup_accountId = account_id AND accgroup_groupId = ?)',
                    'values' => [$GroupData->getUsergroupId(), $GroupData->getUsergroupId()]
                ];
                break;
            case 'maingroup':
                $GroupData = GroupUtil::getGroupIdByName($filters[2]);

                if (!is_object($GroupData)) {
                    return [];
                }

                $filtersData[] = [
                    'type' => 'group',
                    'query' => 'account_userGroupId = ?',
                    'values' => [$GroupData->getUsergroupId()]
                ];
                break;
            case 'file':
                $filtersData[] = [
                    'type' => 'file',
                    'query' => 'account_id IN (SELECT accfile_accountId FROM accFiles WHERE accfile_name LIKE ?)',
                    'values' => ['%' . $filters[2] . '%']
                ];
                break;
            case 'expired':
                $filtersData[] =
                    [
                        'type' => 'expired',
                        'query' => 'account_passDateChange > 0 AND UNIX_TIMESTAMP() > account_passDateChange',
                        'values' => []
                    ];
                break;
            case 'private':
                $filtersData[] =
                    [
                        'type' => 'private',
                        'query' => '(account_isPrivate = 1 AND account_userId = ?) OR (account_isPrivateGroup = 1 AND account_userGroupId = ?)',
                        'values' => [Session::getUserData()->getUserId(), Session::getUserData()->getUserGroupId()]
                    ];
                break;
            default:
                return $filtersData;
        }

        return $filtersData;
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
        return sprintf('%s %s', implode(',', $orderKey), $orderDir);
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
     * @return $this
     */
    public function setSortViews($sortViews)
    {
        $this->sortViews = $sortViews;

        return $this;
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
     * @return $this
     */
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;

        return $this;
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

        if (!is_array($accountColor)
            || !isset($accountColor, $accountColor[$id])
        ) {
            // Se asigna el color de forma aleatoria a cada id
            $color = array_rand(self::$colors);

            $accountColor[$id] = '#' . self::$colors[$color];
            Session::setAccountColor($accountColor);
        }

        return $accountColor[$id];
    }

    /**
     * @return array
     */
    public function getTagsId()
    {
        return $this->tagsId;
    }

    /**
     * @param array $tagsId
     * @return $this
     */
    public function setTagsId($tagsId)
    {
        if (is_array($tagsId)) {
            $this->tagsId = $tagsId;
        }

        return $this;
    }
}