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
use SP\Storage\DB;
use SP\Mgmt\User\Groups;
use SP\Html\Html;
use SP\Core\Session;
use SP\Mgmt\User\UserUtil;
use SP\Storage\QueryData;

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
     * @var bool
     */
    private $_globalSearch = false;
    /**
     * @var string
     */
    private $_txtSearch = '';
    /**
     * @var int
     */
    private $_customerId = 0;
    /**
     * @var int
     */
    private $_categoryId = 0;
    /**
     * @var int
     */
    private $_sortOrder = 0;
    /**
     * @var int
     */
    private $_sortKey = 0;
    /**
     * @var int
     */
    private $_limitStart = 0;
    /**
     * @var int
     */
    private $_limitCount = 12;
    /**
     * @var bool
     */
    private $_sortViews = false;
    /**
     * @var bool
     */
    private $_searchFavorites = false;

    /**
     * Constructor
     */
    function __construct()
    {
        $userResultsPerPage = (Session::getSessionType() === Session::SESSION_INTERACTIVE) ? Session::getUserPreferences()->getResultsPerPage() : 0;

        $this->_limitCount = ($userResultsPerPage > 0) ? $userResultsPerPage : Config::getValue('account_count');
        $this->_sortViews = (Session::getSessionType() === Session::SESSION_INTERACTIVE) ? Session::getUserPreferences()->isSortViews() : false;
    }

    /**
     * @return boolean
     */
    public function isSearchFavorites()
    {
        return $this->_searchFavorites;
    }

    /**
     * @param boolean $searchFavorites
     */
    public function setSearchFavorites($searchFavorites)
    {
        $this->_searchFavorites = (bool)$searchFavorites;
    }

    /**
     * @return int
     */
    public function getGlobalSearch()
    {
        return $this->_globalSearch;
    }

    /**
     * @param int $globalSearch
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->_globalSearch = $globalSearch;
    }

    /**
     * @return string
     */
    public function getTxtSearch()
    {
        return $this->_txtSearch;
    }

    /**
     * @param string $txtSearch
     */
    public function setTxtSearch($txtSearch)
    {
        $this->_txtSearch = (string)$txtSearch;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_customerId;
    }

    /**
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->_customerId = $customerId;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->_categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->_categoryId = $categoryId;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->_sortOrder = $sortOrder;
    }

    /**
     * @return int
     */
    public function getLimitStart()
    {
        return $this->_limitStart;
    }

    /**
     * @param int $limitStart
     */
    public function setLimitStart($limitStart)
    {
        $this->_limitStart = $limitStart;
    }

    /**
     * @return int
     */
    public function getLimitCount()
    {
        return $this->_limitCount;
    }

    /**
     * @param int $limitCount
     */
    public function setLimitCount($limitCount)
    {
        $this->_limitCount = $limitCount;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @return mixed Resultado de la consulta
     */
    public function getAccounts()
    {
        $isAdmin = (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc());

        $arrFilterCommon = array();
        $arrFilterSelect = array();
        $arrFilterUser = array();
        $arrQueryWhere = array();
        $queryLimit = '';

        $Data = new QueryData();

        if ($this->_txtSearch) {
            // Analizar la cadena de búsqueda por etiquetas especiales
            $stringFilters = $this->analyzeQueryString();

            if ($stringFilters !== false) {
                $i = 0;

                foreach ($stringFilters as $column => $value) {
                    $parameter = 'P_' . $column . $i;
                    $rel = '=';

                    if (preg_match('/name/i', $column)) {
                        $rel = 'LIKE';
                        $value = '%' . $value . '%';
                    }

                    $arrFilterCommon[] = $column . ' ' . $rel . ' :' . $parameter;

                    $Data->addParam($value, $parameter);
                    $i++;
                }
            } else {
                $arrFilterCommon[] = 'account_name LIKE :name';
                $arrFilterCommon[] = 'account_login LIKE :login';
                $arrFilterCommon[] = 'account_url LIKE :url';
                $arrFilterCommon[] = 'account_notes LIKE :notes';

                $Data->addParam('%' . $this->_txtSearch . '%', 'name');
                $Data->addParam('%' . $this->_txtSearch . '%', 'login');
                $Data->addParam('%' . $this->_txtSearch . '%', 'url');
                $Data->addParam('%' . $this->_txtSearch . '%', 'notes');
            }
        }

        if ($this->_categoryId !== 0) {
            $arrFilterSelect[] = 'category_id = :categoryId';

            $Data->addParam($this->_categoryId, 'categoryId');
        }

        if ($this->_customerId !== 0) {
            $arrFilterSelect[] = 'account_customerId = :customerId';

            $Data->addParam($this->_customerId, 'customerId');
        }

        if ($this->_searchFavorites === true) {
            $arrFilterSelect[] = 'accFavorites.accfavorite_userId = :favUserId';

            $Data->addParam(Session::getUserId(), 'favUserId');
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        if (!$isAdmin && !$this->_globalSearch) {
            $subQueryGroupsA = '(SELECT user_groupId FROM usrData WHERE user_id = :userIduA UNION ALL SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_userId = :userIdgA)';
            $subQueryGroupsB = '(SELECT user_groupId FROM usrData WHERE user_id = :userIduB UNION ALL SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_userId = :userIdgB)';

            $arrFilterUser[] = 'account_userGroupId IN ' . $subQueryGroupsA;
            $arrFilterUser[] = 'accgroup_groupId IN ' . $subQueryGroupsB;
            $arrFilterUser[] = 'account_userId = :userId';
            $arrFilterUser[] = 'accuser_userId = :accuser_userId';

            // Usuario/Grupo principal de la cuenta
            $Data->addParam(Session::getUserId(), 'userId');
            $Data->addParam(Session::getUserId(), 'accuser_userId');
            $Data->addParam(Session::getUserId(), 'userIduA');
            $Data->addParam(Session::getUserId(), 'userIduB');
            $Data->addParam(Session::getUserId(), 'userIdgA');
            $Data->addParam(Session::getUserId(), 'userIdgB');

            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterUser) . ')';
        }

        if ($this->_limitCount > 0) {
            $queryLimit = 'LIMIT :limitStart,:limitCount';

            $Data->addParam($this->_limitStart, 'limitStart');
            $Data->addParam($this->_limitCount, 'limitCount');
        }

        if (count($arrQueryWhere) === 1) {
            $queryWhere = ' WHERE ' . implode($arrQueryWhere);
        } elseif (count($arrQueryWhere) > 1) {
            $queryWhere = ' WHERE ' . implode(' AND ', $arrQueryWhere);
        } else {
            $queryWhere = '';
        }

        $query = 'SELECT DISTINCT ' .
            'account_id,' .
            'account_customerId,' .
            'category_name,' .
            'account_name,' .
            'account_login,' .
            'account_url,' .
            'account_notes,' .
            'account_userId,' .
            'account_userGroupId,' .
            'BIN(account_otherUserEdit) AS account_otherUserEdit,' .
            'BIN(account_otherGroupEdit) AS account_otherGroupEdit,' .
            'usergroup_name,' .
            'customer_name,' .
            'count(accfile_id) as num_files ' .
            'FROM accounts ' .
            'LEFT JOIN accFiles ON account_id = accfile_accountId ' .
            'LEFT JOIN categories ON account_categoryId = category_id ' .
            'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id ' .
            'LEFT JOIN customers ON customer_id = account_customerId ' .
            'LEFT JOIN accUsers ON accuser_accountId = account_id ' .
            'LEFT JOIN accGroups ON accgroup_accountId = account_id ' .
            'LEFT JOIN accFavorites ON accfavorite_accountId = account_id ' .
            $queryWhere . ' ' .
            'GROUP BY account_id ' .
            $this->getOrderString() . ' ' .
            $queryLimit;

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
        preg_match('/:(user|group|file)\s(.*)/i', $this->_txtSearch, $filters);

        if (!is_array($filters) || count($filters) === 0) {
            return false;
        }

        switch ($filters[1]) {
            case 'user':
                return array(
                    'account_userId' => UserUtil::getUserIdByLogin(Html::sanitize($filters[2])),
                    'accuser_userId' => UserUtil::getUserIdByLogin(Html::sanitize($filters[2]))
                );
                break;
            case 'group':
                return array(
                    'account_userGroupId' => Groups::getGroupIdByName(Html::sanitize($filters[2])),
                    'accgroup_groupId' => Groups::getGroupIdByName(Html::sanitize($filters[2]))
                );
                break;
            case 'file':
                return array(
                    'accfile_name' => Html::sanitize($filters[2])
                );
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
        switch ($this->_sortKey) {
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

        $orderDir = ($this->_sortOrder === self::SORT_DIR_ASC) ? 'ASC' : 'DESC';
        return sprintf('ORDER BY %s %s', implode(',', $orderKey), $orderDir);
    }

    /**
     * @return boolean
     */
    public function isSortViews()
    {
        return $this->_sortViews;
    }

    /**
     * @param boolean $sortViews
     */
    public function setSortViews($sortViews)
    {
        $this->_sortViews = $sortViews;
    }

    /**
     * @return int
     */
    public function getSortKey()
    {
        return $this->_sortKey;
    }

    /**
     * @param int $sortKey
     */
    public function setSortKey($sortKey)
    {
        $this->_sortKey = $sortKey;
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
}