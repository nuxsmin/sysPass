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

use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Session\Session;
use SP\Core\SessionFactory;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\AccountSearchData;
use SP\Mgmt\Groups\GroupUtil;
use SP\Mgmt\Users\User;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
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
     * @var Session
     */
    protected $session;
    /**
     * @var ConfigData
     */
    protected $configData;
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
    private $sortViews;
    /**
     * @var bool
     */
    private $searchFavorites = false;

    use InjectableTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->injectDependencies();

        $userResultsPerPage = (SessionFactory::getSessionType() === SessionFactory::SESSION_INTERACTIVE) ? $this->session->getUserPreferences()->getResultsPerPage() : 0;

        $this->limitCount = ($userResultsPerPage > 0) ? $userResultsPerPage : $this->configData->getAccountCount();
        $this->sortViews = (SessionFactory::getSessionType() === SessionFactory::SESSION_INTERACTIVE) ? $this->session->getUserPreferences()->isSortViews() : false;
    }

    /**
     * @param ConfigData $configData
     * @param Session    $session
     */
    public function inject(ConfigData $configData, Session $session)
    {
        $this->configData = $configData;
        $this->session = $session;
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
     * @throws \SP\Core\Exceptions\SPException
     */
    public function processSearchResults()
    {
        if (!$results = $this->getAccounts()) {
            return [];
        }

        // Variables de configuración
        $maxTextLength = $this->configData->isResultsAsCards() ? 40 : 60;

        $accountsData['count'] = self::$queryNumRows;

        $accountLinkEnabled = $this->session->getUserPreferences()->isAccountLink() || $this->configData->isAccountLink();
        $favorites = AccountFavorites::getFavorites($this->session->getUserData()->getUserId());

        /** @var AccountSearchData $accountSearchData */
        foreach ($results as $accountSearchData) {
            // Propiedades de búsqueda de cada cuenta
            $accountsSearchItem = new AccountsSearchItem($accountSearchData);

            // Obtener la ACL de la cuenta
            $accountAcl = new AccountAcl(Acl::ACCOUNT_SEARCH, $accountSearchData);

            if (!$accountSearchData->getAccountIsPrivate()) {
                $accountSearchData->setUsersId($accountsSearchItem->getCacheUsers(true));
                $accountSearchData->setUserGroupsId($accountsSearchItem->getCacheGroups(true));
            }

            $accountSearchData->setTags(AccountTags::getTags($accountSearchData));

            // Obtener la ACL
            $acl = $accountAcl->getAcl();

            $this->session->setAccountAcl($acl);

            $accountsSearchItem->setTextMaxLength($maxTextLength);
            $accountsSearchItem->setColor($this->pickAccountColor($accountSearchData->getAccountCustomerId()));
            $accountsSearchItem->setShowView($acl->isShowView());
            $accountsSearchItem->setShowViewPass($acl->isShowViewPass());
            $accountsSearchItem->setShowEdit($acl->isShowEdit());
            $accountsSearchItem->setShowCopy($acl->isShowCopy());
            $accountsSearchItem->setShowDelete($acl->isShowDelete());
            $accountsSearchItem->setLink($accountLinkEnabled);
            $accountsSearchItem->setFavorite(in_array($accountSearchData->getAccountId(), $favorites, true));

            $accountsData[] = $accountsSearchItem;
        }

        return $accountsData;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @return AccountSearchData[] Resultado de la consulta
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccounts()
    {
        $arrFilterCommon = [];
        $arrFilterSelect = [];
        $arrayQueryJoin = [];
        $arrQueryWhere = [];
        $queryLimit = '';

        $data = new QueryData();
        $data->setMapClassName(AccountSearchData::class);

        if ($this->txtSearch !== null && $this->txtSearch !== '') {
            // Analizar la cadena de búsqueda por etiquetas especiales
            $stringFilters = $this->analyzeQueryString();

            if (count($stringFilters) > 0) {
                foreach ($stringFilters as $filter) {
                    $arrFilterCommon[] = $filter['query'];

                    foreach ($filter['values'] as $value) {
                        $data->addParam($value);
                    }
                }
            } else {
                $txtSearch = '%' . $this->txtSearch . '%';

                $arrFilterCommon[] = 'account_name LIKE ?';
                $data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_login LIKE ?';
                $data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_url LIKE ?';
                $data->addParam($txtSearch);

                $arrFilterCommon[] = 'account_notes LIKE ?';
                $data->addParam($txtSearch);
            }
        }

        if ($this->categoryId !== 0) {
            $arrFilterSelect[] = 'account_categoryId = ?';
            $data->addParam($this->categoryId);
        }

        if ($this->customerId !== 0) {
            $arrFilterSelect[] = 'account_customerId = ?';
            $data->addParam($this->customerId);
        }

        $numTags = count($this->tagsId);

        if ($numTags > 0) {
            $tags = str_repeat('?,', $numTags - 1) . '?';

            $arrFilterSelect[] = 'account_id IN (SELECT acctag_accountId FROM accTags WHERE acctag_tagId IN (' . $tags . '))';

            for ($i = 0; $i <= $numTags - 1; $i++) {
                $data->addParam($this->tagsId[$i]);
            }
        }

        if ($this->searchFavorites === true) {
            $arrayQueryJoin[] = 'INNER JOIN accFavorites ON (accfavorite_accountId = account_id AND accfavorite_userId = ?)';
            $data->addParam($this->session->getUserData()->getUserId());
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        $arrQueryWhere = array_merge($arrQueryWhere, AccountUtil::getAccountFilterUser($data, $this->session, $this->globalSearch));

        if ($this->limitCount > 0) {
            $queryLimit = '?, ?';

            $data->addParam($this->limitStart);
            $data->addParam($this->limitCount);
        }

        $queryWhere = '';

        if (count($arrQueryWhere) === 1) {
            $queryWhere = implode($arrQueryWhere);
        } elseif (count($arrQueryWhere) > 1) {
            $queryWhere = implode(' AND ', $arrQueryWhere);
        }

        $queryJoin = implode('', $arrayQueryJoin);

        $data->setSelect('*');
        $data->setFrom('account_search_v ' . $queryJoin);
        $data->setWhere($queryWhere);
        $data->setOrder($this->getOrderString());
        $data->setLimit($queryLimit);

        // Obtener el número total de cuentas visibles por el usuario
        DbWrapper::setFullRowCount();

//        Log::writeNewLog(__FUNCTION__, $Data->getQuery(), Log::DEBUG);
//        Log::writeNewLog(__FUNCTION__, print_r($Data->getParams(), true), Log::DEBUG);

        // Consulta de la búsqueda de cuentas
        $queryRes = DbWrapper::getResultsArray($data);

        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        self::$queryNumRows = $data->getQueryNumRows();

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
                        'values' => [$this->session->getUserData()->getUserId(), $this->session->getUserData()->getUserGroupId()]
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
        $accountColor = SessionFactory::getAccountColor();

        if (!is_array($accountColor)
            || !isset($accountColor, $accountColor[$id])
        ) {
            // Se asigna el color de forma aleatoria a cada id
            $color = array_rand(self::$colors);

            $accountColor[$id] = '#' . self::$colors[$color];
            SessionFactory::setAccountColor($accountColor);
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

    /**
     * unserialize() checks for the presence of a function with the magic name __wakeup.
     * If present, this function can reconstruct any resources that the object may have.
     * The intended use of __wakeup is to reestablish any database connections that may have been lost during
     * serialization and perform other reinitialization tasks.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __wakeup()
    {
        $this->injectDependencies();
    }

    /**
     * serialize() checks if your class has a function with the magic name __sleep.
     * If so, that function is executed prior to any serialization.
     * It can clean up the object and is supposed to return an array with the names of all variables of that object that should be serialized.
     * If the method doesn't return anything then NULL is serialized and E_NOTICE is issued.
     * The intended use of __sleep is to commit pending data or perform similar cleanup tasks.
     * Also, the function is useful if you have very large objects which do not need to be saved completely.
     *
     * @return string[]
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __sleep()
    {
        unset($this->dic, $this->configData, $this->session);

        $props = [];

        foreach ((array)$this as $prop => $value) {
            if ($prop !== "\0*\0configData"
                && $prop !== "\0*\0dic"
                && $prop !== "\0*\0session") {
                $props[] = $prop;
            }
        }

        return $props;
    }
}