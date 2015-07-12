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

namespace SP;

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

    /**
     * @var int El número de registros de la última consulta
     */
    public static $queryNumRows;

    private $_globalSearch = false;
    private $_txtSearch = '';
    private $_customerId = 0;
    private $_categoryId = 0;
    private $_sortOrder = 0;
    private $_sortKey = 0;
    private $_limitStart = 0;
    private $_limitCount = 12;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->setLimitCount(Config::getValue('account_count'));
    }

    /**
     * @return boolean
     */
    public function isGlobalSearch()
    {
        return $this->_globalSearch;
    }

    /**
     * @param boolean $globalSearch
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
        $this->_txtSearch = $txtSearch;
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
     * @return bool Resultado de la consulta
     */
    public function getAccounts()
    {
        $isAdmin = (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc());
        $globalSearch = ($this->isGlobalSearch() && Config::getValue('globalsearch', 0));

        $arrFilterCommon = array();
        $arrFilterSelect = array();
        $arrFilterUser = array();
        $arrQueryWhere = array();

        switch ($this->getSortKey()) {
            case self::SORT_NAME:
                $orderKey = 'account_name';
                break;
            case self::SORT_CATEGORY:
                $orderKey = 'category_name';
                break;
            case self::SORT_LOGIN:
                $orderKey = 'account_login';
                break;
            case self::SORT_URL:
                $orderKey = 'account_url';
                break;
            case self::SORT_CUSTOMER:
                $orderKey = 'customer_name';
                break;
            default :
                $orderKey = 'customer_name, account_name';
                break;
        }

        $querySelect = 'SELECT DISTINCT '
            . 'account_id,'
            . 'account_customerId,'
            . 'category_name,'
            . 'account_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_notes,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'BIN(account_otherUserEdit) AS account_otherUserEdit,'
            . 'BIN(account_otherGroupEdit) AS account_otherGroupEdit,'
            . 'usergroup_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN categories ON account_categoryId = category_id '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN customers ON customer_id = account_customerId '
            . 'LEFT JOIN accUsers ON accuser_accountId = account_id '
            . 'LEFT JOIN accGroups ON accgroup_accountId = account_id';

        if ($this->getTxtSearch()) {
            $arrFilterCommon[] = 'account_name LIKE :name';
            $arrFilterCommon[] = 'account_login LIKE :login';
            $arrFilterCommon[] = 'account_url LIKE :url';
            $arrFilterCommon[] = 'account_notes LIKE :notes';

            $data['name'] = '%' . $this->getTxtSearch() . '%';
            $data['login'] = '%' . $this->getTxtSearch() . '%';
            $data['url'] = '%' . $this->getTxtSearch() . '%';
            $data['notes'] = '%' . $this->getTxtSearch() . '%';
        }

        if ($this->getCategoryId() !== 0) {
            $arrFilterSelect[] = 'category_id = :categoryId';

            $data['categoryId'] = $this->getCategoryId();
        }

        if ($this->getCustomerId() !== 0) {
            $arrFilterSelect[] = 'account_customerId = :customerId';

            $data['customerId'] = $this->getCustomerId();
        }

        if (count($arrFilterCommon) > 0) {
            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterCommon) . ')';
        }

        if (count($arrFilterSelect) > 0) {
            $arrQueryWhere[] = '(' . implode(' AND ', $arrFilterSelect) . ')';
        }

        if (!$isAdmin && !$globalSearch) {
            $arrFilterUser[] = 'account_userGroupId = :userGroupId';
            $arrFilterUser[] = 'account_userId = :userId';
            $arrFilterUser[] = 'accgroup_groupId = :accgroup_groupId';
            $arrFilterUser[] = 'accuser_userId = :accuser_userId';

            // Usuario/Grupo principal de la cuenta
            $data['userId'] = Session::getUserId();
            $data['accuser_userId'] = Session::getUserId();

            // Usuario/Grupo secundario de la cuenta
            $data['userGroupId'] = Session::getUserGroupId();
            $data['accgroup_groupId'] = Session::getUserGroupId();

            $arrQueryWhere[] = '(' . implode(' OR ', $arrFilterUser) . ')';
        }

        $orderDir = ($this->getSortOrder() === 0) ? 'ASC' : 'DESC';
        $queryOrder = 'ORDER BY ' . $orderKey . ' ' . $orderDir;

        if ($this->getLimitCount() != 99) {
            $queryLimit = 'LIMIT :limitStart,:limitCount';

            $data['limitStart'] = $this->getLimitStart();
            $data['limitCount'] = $this->getLimitCount();
        }

        if (count($arrQueryWhere) === 1) {
            $query = $querySelect . ' WHERE ' . implode($arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } elseif (count($arrQueryWhere) > 1) {
            $query = $querySelect . ' WHERE ' . implode(' AND ', $arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } else {
            $query = $querySelect . ' ' . $queryOrder . ' ' . $queryLimit;
        }

        // Obtener el número total de cuentas visibles por el usuario
        DB::setFullRowCount();

        // Obtener los resultados siempre en array de objetos
        DB::setReturnArray();

        // Consulta de la búsqueda de cuentas
        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
//            print_r($query);
//            var_dump($data);
            return false;
        }

        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        self::$queryNumRows = DB::$lastNumRows;

        // Establecer el filtro de búsqueda en la sesión como un objeto
        Session::setSearchFilters($this);

        return $queryRes;
    }

    /**
     * Obtiene el número de cuentas que un usuario puede ver.
     *
     * @return false|int con el número de registros
     */
    public function getAccountMax()
    {
        $data = null;

        if (!Session::getUserIsAdminApp() && !Session::getUserIsAdminAcc()) {
            $query = 'SELECT COUNT(DISTINCT account_id) as numacc '
                . 'FROM accounts '
                . 'LEFT JOIN accGroups ON account_id = accgroup_accountId '
                . 'WHERE account_userGroupId = :userGroupId '
                . 'OR account_userId = :userId '
                . 'OR accgroup_groupId = :groupId';

            $data['userGroupId'] = Session::getUserGroupId();
            $data['groupId'] = Session::getUserGroupId();
            $data['userId'] = Session::getUserId();

        } else {
            $query = "SELECT COUNT(*) as numacc FROM accounts";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->numacc;
    }
}