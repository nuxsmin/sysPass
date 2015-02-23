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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre las cuentas de sysPass.
 */
class SP_Account
{
    static $accountSearchTxt;
    static $accountSearchCustomer;
    static $accountSearchCategory;
    static $accountSearchOrder;
    static $accountSearchKey;

    var $accountId;
    var $accountParentId;
    var $accountUserId;
    var $accountUsersId;
    var $accountUserGroupId;
    var $accountUserGroupsId;
    var $accountUserEditId;
    var $accountName;
    var $accountCustomerId;
    var $accountCategoryId;
    var $accountLogin;
    var $accountUrl;
    var $accountPass;
    var $accountIV;
    var $accountNotes;
    var $accountOtherUserEdit;
    var $accountOtherGroupEdit;
    var $accountModHash;

    var $lastAction;
    var $lastId;
    var $query; // Variable de consulta
    var $queryNumRows;
    var $accountIsHistory = 0; // Variable para indicar si la cuenta es desde el histórico
    var $accountCacheUserGroupsId; // Cache para grupos de usuarios de las cuentas
    var $accountCacheUsersId; // Cache para usuarios de las cuentas

    // Variable para la caché de parámetros
    var $cacheParams;

    /**
     * Obtener los datos de usuario y modificador de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|object con el id de usuario y modificador.
     */
    public static function getAccountRequestData($accountId)
    {
        $query = 'SELECT account_userId,'
            . 'account_userEditId,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes;
    }

    /**
     * Obtiene el listado con el nombre de los usuaios de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los nombres de los usuarios ordenados
     */
    public static function getAccountUsersName($accountId)
    {
        $query = 'SELECT user_name '
            . 'FROM accUsers '
            . 'JOIN usrData ON accuser_userId = user_id '
            . 'WHERE accuser_accountId = :id';

        $data['id'] = $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        if (!is_array($queryRes)) {
            return false;
        }

        foreach ($queryRes as $users) {
            $usersName[] = $users->user_name;
        }

        sort($usersName, SORT_STRING);

        return $usersName;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param array $searchFilter Filtros de búsqueda
     * @return bool Resultado de la consulta
     */
    public function getAccounts($searchFilter)
    {
        $isAdmin = ($_SESSION['uisadminapp'] || $_SESSION['uisadminacc']);
        $globalSearch = ($searchFilter['globalSearch'] === 1 && SP_Config::getValue('globalsearch', 0));

        $arrFilterCommon = array();
        $arrFilterSelect = array();
        $arrFilterUser = array();
        $arrQueryWhere = array();

        switch ($searchFilter['keyId']) {
            case 1:
                $orderKey = 'account_name';
                break;
            case 2:
                $orderKey = 'category_name';
                break;
            case 3:
                $orderKey = 'account_login';
                break;
            case 4:
                $orderKey = 'account_url';
                break;
            case 5:
                $orderKey = 'account_customerId';
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

        if ($searchFilter['txtSearch']) {
            $arrFilterCommon[] = 'account_name LIKE :name';
            $arrFilterCommon[] = 'account_login LIKE :login';
            $arrFilterCommon[] = 'account_url LIKE :url';
            $arrFilterCommon[] = 'account_notes LIKE :notes';

            $data['name'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['login'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['url'] = '%' . $searchFilter['txtSearch'] . '%';
            $data['notes'] = '%' . $searchFilter['txtSearch'] . '%';
        }

        if ($searchFilter['categoryId'] != 0) {
            $arrFilterSelect[] = 'category_id = :categoryId';

            $data['categoryId'] = $searchFilter['categoryId'];
        }
        if ($searchFilter['customerId'] != 0) {
            $arrFilterSelect[] = 'account_customerId = :customerId';

            $data['customerId'] = $searchFilter['customerId'];
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

            $data['userGroupId'] = $searchFilter['groupId'];
            $data['userId'] = $searchFilter['userId'];
            $data['accgroup_groupId'] = $searchFilter['groupId'];
            $data['accuser_userId'] = $searchFilter['userId'];

            //$arrQueryWhere[] = '(' . implode(' OR ', $arrFilterUser) . ')';
            $arrQueryWhere[] = implode(' OR ', $arrFilterUser);
        }

        $orderDir = ($searchFilter["txtOrder"] == 0) ? 'ASC' : 'DESC';
        $queryOrder = 'ORDER BY ' . $orderKey . ' ' . $orderDir;

        if ($searchFilter['limitCount'] != 99) {
            $queryLimit = 'LIMIT :limitStart,:limitCount';

            $data['limitStart'] = $searchFilter['limitStart'];
            $data['limitCount'] = $searchFilter['limitCount'];
        }

        if (count($arrQueryWhere) === 1) {
            $query = $querySelect . ' WHERE ' . implode($arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } elseif (count($arrQueryWhere) > 1) {
            $query = $querySelect . ' WHERE ' . implode(' AND ', $arrQueryWhere) . ' ' . $queryOrder . ' ' . $queryLimit;
        } else {
            $query = $querySelect . ' ' . $queryOrder . ' ' . $queryLimit;
        }

        $this->query = $query;

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
        $this->queryNumRows = DB::$last_num_rows;

        $_SESSION["accountSearchTxt"] = $searchFilter["txtSearch"];
        $_SESSION["accountSearchCustomer"] = $searchFilter["customerId"];
        $_SESSION["accountSearchCategory"] = $searchFilter["categoryId"];
        $_SESSION["accountSearchOrder"] = $searchFilter["txtOrder"];
        $_SESSION["accountSearchKey"] = $searchFilter["keyId"];
        $_SESSION["accountSearchStart"] = $searchFilter["limitStart"];
        $_SESSION["accountSearchLimit"] = $searchFilter["limitCount"];
        $_SESSION["accountGlobalSearch"] = $searchFilter["globalSearch"];

        return $queryRes;
    }

    /**
     * Obtener los datos del histórico de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos del histórico
     * en las variables de la clase.
     *
     * @return false|object
     */
    public function getAccountHistory()
    {
        $query = 'SELECT acchistory_accountId as account_id,'
            . 'acchistory_customerId as account_customerId,'
            . 'acchistory_categoryId as account_categoryId,'
            . 'acchistory_name as account_name,'
            . 'acchistory_login as account_login,'
            . 'acchistory_url as account_url,'
            . 'acchistory_pass as account_pass,'
            . 'acchistory_IV as account_IV,'
            . 'acchistory_notes as account_notes,'
            . 'acchistory_countView as account_countView,'
            . 'acchistory_countDecrypt as account_countDecrypt,'
            . 'acchistory_dateAdd as account_dateAdd,'
            . 'acchistory_dateEdit as account_dateEdit,'
            . 'acchistory_userId as account_userId,'
            . 'acchistory_userGroupId as account_userGroupId,'
            . 'acchistory_userEditId as account_userEditId,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_otherUserEdit + 0 AS account_otherUserEdit,'
            . 'acchistory_otherGroupEdit + 0 AS account_otherGroupEdit,'
            . 'u1.user_name,'
            . 'u1.user_login,'
            . 'usergroup_name,'
            . 'u2.user_name as user_editName,'
            . 'u2.user_login as user_editLogin,'
            . 'category_name, customer_name '
            . 'FROM accHistory '
            . 'LEFT JOIN categories ON acchistory_categoryId = category_id '
            . 'LEFT JOIN usrGroups ON acchistory_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON acchistory_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON acchistory_customerId = customer_id '
            . 'WHERE acchistory_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->accountUserId = $queryRes->account_userId;
        $this->accountUserGroupId = $queryRes->account_userGroupId;
        $this->accountOtherUserEdit = $queryRes->account_otherUserEdit;
        $this->accountOtherGroupEdit = $queryRes->account_otherGroupEdit;

        return $queryRes;
    }

    /**
     * Actualiza los datos de una cuenta en la BBDD.
     *
     * @param bool $isRestore si es una restauración de cuenta
     * @return bool
     */
    public function updateAccount($isRestore = false)
    {
        $message['action'] = __FUNCTION__;

        // Guardamos una copia de la cuenta en el histórico
        if (!$this->addHistory($this->accountId, $this->accountUserEditId, false)) {
            $message['text'][] = _('Error al actualizar el historial');
            SP_Log::wrLogInfo($message);
            return false;
        }

        if (!$isRestore) {
            $message['action'] = _('Actualizar Cuenta');

            if (!SP_Groups::updateGroupsForAccount($this->accountId, $this->accountUserGroupsId)) {
                $message['text'][] = _('Error al actualizar los grupos secundarios');
                SP_Log::wrLogInfo($message);
                $message['text'] = array();
            }

            if (!SP_Users::updateUsersForAccount($this->accountId, $this->accountUsersId)) {
                $message['text'][] = _('Error al actualizar los usuarios de la cuenta');
                SP_Log::wrLogInfo($message);
                $message['text'] = array();
            }
        } else {
            $message['action'] = _('Restaurar Cuenta');
        }

        $query = 'UPDATE accounts SET '
            . 'account_customerId = :accountCustomerId,'
            . 'account_categoryId = :accountCategoryId,'
            . 'account_name = :accountName,'
            . 'account_login = :accountLogin,'
            . 'account_url = :accountUrl,'
            . 'account_notes = :accountNotes,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_dateEdit = NOW(),'
            . 'account_otherUserEdit = :accountOtherUserEdit,'
            . 'account_otherGroupEdit = :accountOtherGroupEdit '
            . 'WHERE account_id = :accountId';

        $data['accountCustomerId'] = $this->accountCustomerId;
        $data['accountCategoryId'] = $this->accountCategoryId;
        $data['accountName'] = $this->accountName;
        $data['accountLogin'] = $this->accountLogin;
        $data['accountUrl'] = $this->accountUrl;
        $data['accountNotes'] = $this->accountNotes;
        $data['accountUserEditId'] = $this->accountUserEditId;
        $data['accountOtherUserEdit'] = intval($this->accountOtherUserEdit);
        $data['accountOtherGroupEdit'] = intval($this->accountOtherGroupEdit);
        $data['accountId'] = $this->accountId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);

        $message['action'] = _('Cuenta actualizada');
        $message['text'][] = SP_Html::strongText(_('Cliente') . ': ') . $this->cacheParams['customer_name'];
        $message['text'][] = SP_Html::strongText(_('Cuenta') . ': ') . "$this->accountName ($this->accountId)";

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Crear un nuevo registro de histório de cuenta en la BBDD.
     *
     * @param bool $isDelete indica que la cuenta es eliminada
     * @return bool
     */
    private function addHistory($isDelete = false)
    {
        $query = 'INSERT INTO accHistory '
            . '(acchistory_accountId,'
            . 'acchistory_categoryId,'
            . 'acchistory_customerId,'
            . 'acchistory_name,'
            . 'acchistory_login,'
            . 'acchistory_url,'
            . 'acchistory_pass,'
            . 'acchistory_IV,'
            . 'acchistory_notes,'
            . 'acchistory_countView,'
            . 'acchistory_countDecrypt,'
            . 'acchistory_dateAdd,'
            . 'acchistory_dateEdit,'
            . 'acchistory_userId,'
            . 'acchistory_userGroupId,'
            . 'acchistory_userEditId,'
            . 'acchistory_otherUserEdit,'
            . 'acchistory_otherGroupEdit,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_mPassHash) '
            . 'SELECT account_id,'
            . 'account_categoryId,'
            . 'account_customerId,'
            . 'account_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_IV,'
            . 'account_notes,'
            . 'account_countView,'
            . 'account_countDecrypt,'
            . 'account_dateAdd,'
            . 'account_dateEdit,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'account_userEditId,'
            . 'account_otherUserEdit,'
            . 'account_otherGroupEdit,'
            . ':isModify,'
            . ':isDelete,'
            . ':masterPwd '
            . 'FROM accounts WHERE account_id = :account_id';

        $data['account_id'] = $this->accountId;
        $data['isModify'] = ($isDelete === false) ? 1 : 0;
        $data['isDelete'] = ($isDelete === false) ? 0 : 1;
        $data['masterPwd'] = SP_Config::getConfigValue('masterPwd');

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Obtener los datos de una cuenta con el id.
     * Se guardan los datos en la variable $cacheParams de la clase para consultarlos
     * posteriormente.
     *
     * @param array $params con los campos de la BBDD a obtener
     * @return bool
     */
    private function getAccountInfoById($params)
    {
        if (!is_array($params)) {
            return false;
        }

        if (is_array($this->cacheParams)) {
            $cache = true;

            foreach ($params as $param) {
                if (!array_key_exists($param, $this->cacheParams)) {
                    $cache = false;
                }
            }

            if ($cache) {
                return true;
            }
        }

        $query = 'SELECT ' . implode(',', $params) . ' '
            . 'FROM accounts '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON account_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON account_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $param => $value) {
            $this->cacheParams[$param] = $value;
        }

        return true;
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return object|false
     */
    public function getAccountPass($isHistory = false)
    {
        if (!$isHistory) {
            $query = 'SELECT account_name AS name,'
                . 'account_userId AS userId,'
                . 'account_userGroupId AS groupId,'
                . 'account_login AS login,'
                . 'account_pass AS pass,'
                . 'account_IV AS iv,'
                . 'customer_name '
                . 'FROM accounts '
                . 'LEFT JOIN customers ON account_customerId = customer_id '
                . 'WHERE account_id = :id LIMIT 1';
        } else {
            $query = 'SELECT acchistory_name AS name,'
                . 'acchistory_userId AS userId,'
                . 'acchistory_userGroupId AS groupId,'
                . 'acchistory_login AS login,'
                . 'acchistory_pass AS pass,'
                . 'acchistory_IV AS iv,'
                . 'customer_name '
                . 'FROM accHistory '
                . 'LEFT JOIN customers ON acchistory_customerId = customer_id '
                . 'WHERE acchistory_id = :id LIMIT 1';
        }

        $data['id'] = $this->accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->accountUserId = $queryRes->userId;
        $this->accountUserGroupId = $queryRes->groupId;
        $this->accountPass = $queryRes->pass;
        $this->accountIV = $queryRes->iv;

        return $queryRes;
    }

    /**
     * Obtener los datos de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos en las variables de la clase.
     *
     * @return object|false
     */
    public function getAccount()
    {
        $query = 'SELECT account_id,'
            . 'account_name,'
            . 'account_categoryId,'
            . 'account_userId,'
            . 'account_customerId,'
            . 'account_userGroupId,'
            . 'account_userEditId,'
            . 'category_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_IV,'
            . 'account_notes,'
            . 'account_countView,'
            . 'account_countDecrypt,'
            . 'account_dateAdd,'
            . 'account_dateEdit,'
            . 'BIN(account_otherUserEdit) AS account_otherUserEdit,'
            . 'BIN(account_otherGroupEdit) AS account_otherGroupEdit,'
            . 'u1.user_name,'
            . 'u1.user_login,'
            . 'u2.user_name as user_editName,'
            . 'u2.user_login as user_editLogin,'
            . 'usergroup_name,'
            . 'customer_name, '
            . 'CONCAT(account_name,account_categoryId,account_customerId,account_login,account_url,account_notes,BIN(account_otherUserEdit),BIN(account_otherGroupEdit)) as modHash '
            . 'FROM accounts '
            . 'LEFT JOIN categories ON account_categoryId = category_id '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON account_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON account_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->accountUserId = $queryRes->account_userId;
        $this->accountUserGroupId = $queryRes->account_userGroupId;
        $this->accountOtherUserEdit = $queryRes->account_otherUserEdit;
        $this->accountOtherGroupEdit = $queryRes->account_otherGroupEdit;
        $this->accountModHash = $queryRes->modHash;

        return $queryRes;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @return bool
     */
    public function createAccount()
    {
        $query = 'INSERT INTO accounts SET '
            . 'account_customerId = :accountCustomerId,'
            . 'account_categoryId = :accountCategoryId,'
            . 'account_name = :accountName,'
            . 'account_login = :accountLogin,'
            . 'account_url = :accountUrl,'
            . 'account_pass = :accountPass,'
            . 'account_IV = :accountIV,'
            . 'account_notes = :accountNotes,'
            . 'account_dateAdd = NOW(),'
            . 'account_userId = :accountUserId,'
            . 'account_userGroupId = :accountUserGroupId,'
            . 'account_otherUserEdit = :accountOtherUserEdit,'
            . 'account_otherGroupEdit = :accountOtherGroupEdit';

        $data['accountCustomerId'] = $this->accountCustomerId;
        $data['accountCategoryId'] = $this->accountCategoryId;
        $data['accountName'] = $this->accountName;
        $data['accountLogin'] = $this->accountLogin;
        $data['accountUrl'] = $this->accountUrl;
        $data['accountPass'] = $this->accountPass;
        $data['accountIV'] = $this->accountIV;
        $data['accountNotes'] = $this->accountNotes;
        $data['accountUserId'] = $this->accountUserId;
        $data['accountUserGroupId'] = $this->accountUserGroupId;
        $data['accountOtherUserEdit'] = $this->accountOtherUserEdit;
        $data['accountOtherGroupEdit'] = $this->accountOtherGroupEdit;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->accountId = DB::$lastId;

        $message['action'] = __FUNCTION__;

        if (is_array($this->accountUserGroupsId)) {
            if (!SP_Groups::addGroupsForAccount($this->accountId, $this->accountUserGroupsId)) {
                $message['text'][] = _('Error al actualizar los grupos secundarios');
                SP_Log::wrLogInfo($message);
                $message['text'] = array();
            }
        }

        if (is_array($this->accountUsersId)) {
            if (!SP_Users::addUsersForAccount($this->accountId, $this->accountUsersId)) {
                $message['text'][] = _('Error al actualizar los usuarios de la cuenta');
                SP_Log::wrLogInfo($message);
                $message['text'] = array();
            }
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);

        $message['action'] = _('Nueva Cuenta');
        $message['text'][] = SP_Html::strongText(_('Cliente') . ': ') . $this->cacheParams['customer_name'];
        $message['text'][] = SP_Html::strongText(_('Cuenta') . ': ') . "$this->accountName ($this->accountId)";

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @return bool
     */
    public function deleteAccount()
    {
        // Guardamos una copia de la cuenta en el histórico
        $this->addHistory(true) || die (_('ERROR: Error en la operación.'));

        $accountInfo = array('account_name,customer_name');
        $this->getAccountInfoById($accountInfo);

        $message['action'] = _('Eliminar Cuenta');
        $message['text'][] = SP_Html::strongText(_('Cliente') . ': ') . $this->cacheParams['customer_name'];
        $message['text'][] = SP_Html::strongText(_('Cuenta') . ': ') . $this->cacheParams['account_name'] . " ($this->accountId)";

        $query = 'DELETE FROM accounts WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        if (!SP_Groups::deleteGroupsForAccount($this->accountId)) {
            $message['text'][] = _('Error al eliminar grupos asociados a la cuenta');
        }

        if (!SP_Users::deleteUsersForAccount($this->accountId)) {
            $message['text'][] = _('Error al eliminar usuarios asociados a la cuenta');
        }

        if (!SP_Files::deleteAccountFiles($this->accountId)) {
            $message['text'][] = _('Error al eliminar archivos asociados a la cuenta');
        }

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @return false|array Con los registros con id como clave y fecha - usuario como valor
     */
    public function getAccountHistoryList()
    {
        $query = 'SELECT acchistory_id,'
            . 'acchistory_dateEdit,'
            . 'u1.user_login as user_edit,'
            . 'u2.user_login as user_add,'
            . 'acchistory_dateAdd '
            . 'FROM accHistory '
            . 'LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id '
            . 'WHERE acchistory_accountId = :id '
            . 'ORDER BY acchistory_id DESC';

        $data['id'] = $_SESSION["accParentId"];

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $arrHistory = array();

        foreach ($queryRes as $history) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if ($history->acchistory_dateEdit === null || $history->acchistory_dateEdit == '0000-00-00 00:00:00') {
                    $arrHistory[$history->acchistory_id] = $history->acchistory_dateAdd . ' - ' . $history->user_add;
            } else {
                $arrHistory[$history->acchistory_id] = $history->acchistory_dateEdit . ' - ' . $history->user_edit;
            }
        }

        return $arrHistory;
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @return bool
     */
    public function incrementViewCounter()
    {
        $query = 'UPDATE accounts SET account_countView = (account_countView + 1) WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @return bool
     */
    public function incrementDecryptCounter()
    {
        $query = 'UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->accountId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Obtiene el número de cuentas que un usuario puede ver.
     *
     * @return false|int con el número de registros
     */
    public function getAccountMax()
    {
        $userGroupId = $_SESSION["ugroup"];
        $userId = $_SESSION["uid"];
        $userIsAdminApp = $_SESSION['uisadminapp'];
        $userIsAdminAcc = $_SESSION['uisadminacc'];

        $data = null;

        if (!$userIsAdminApp && !$userIsAdminAcc) {
            $query = 'SELECT COUNT(DISTINCT account_id) as numacc '
                . 'FROM accounts '
                . 'LEFT JOIN accGroups ON account_id = accgroup_accountId '
                . 'WHERE account_userGroupId = :userGroupId '
                . 'OR account_userId = :userId '
                . 'OR accgroup_groupId = :groupId';

            $data['userGroupId'] = $userGroupId;
            $data['groupId'] = $userGroupId;
            $data['userId'] = $userId;

        } else {
            $query = "SELECT COUNT(*) as numacc FROM accounts";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->numacc;
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass     con la nueva clave maestra
     * @return bool
     */
    public function updateAllAccountsMPass($currentMasterPass, $newMasterPass)
    {
        $accountsOk = array();
        $userId = $_SESSION['uid'];
        $errorCount = 0;
        $demoEnabled = SP_Util::demoIsEnabled();

        $message['action'] = _('Actualizar Clave Maestra');
        $message['text'][] = _('Inicio');

        SP_Log::wrLogInfo($message);

        // Limpiar 'text' para los próximos mensajes
        $message['text'] = array();

        if (!SP_Crypt::checkCryptModule()) {
            $message['text'][] = _('Error en el módulo de encriptación');
            SP_Log::wrLogInfo($message);
            return false;
        }

        $accountsPass = $this->getAccountsPassData();

        if (!$accountsPass) {
            $message['text'][] = _('Error al obtener las claves de las cuentas');
            SP_Log::wrLogInfo($message);
            return false;
        }

        foreach ($accountsPass as $account) {
            $this->accountId = $account->account_id;
            $this->accountUserEditId = $userId;

            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $accountsOk[] = $this->accountId;
                continue;
            }

            if (strlen($account->account_IV) < 32){
                $errorCount++;
                $message['text'][] = _('IV de encriptación incorrecto') . " (" . $account->account_id . ")";
                continue;
            }

            $decryptedPass = SP_Crypt::getDecrypt($account->account_pass, $currentMasterPass, $account->account_IV);
            $this->accountPass = SP_Crypt::mkEncrypt($decryptedPass, $newMasterPass);
            $this->accountIV = SP_Crypt::$strInitialVector;

            if ($this->accountPass === false) {
                $errorCount++;
                continue;
            }

            if (!$this->updateAccountPass(true)) {
                $errorCount++;
                $message['text'][] = _('Fallo al actualizar la clave de la cuenta') . '(' . $this->accountId . ')';
                continue;
            }

            $accountsOk[] = $this->accountId;
        }

        // Vaciar el array de mensaje de log
        if (count($message['text']) > 0) {
            SP_Log::wrLogInfo($message);
            $message['text'] = array();
        }

        if ($accountsOk) {
            $message['text'][] = _('Cuentas actualizadas') . ': ' . implode(',', $accountsOk);
            SP_Log::wrLogInfo($message);
            $message['text'] = array();
        }

        $message['text'][] = _('Fin');
        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        if ($errorCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return false|array Con los datos de la clave
     */
    private function getAccountsPassData()
    {
        $query = 'SELECT account_id, account_pass, account_IV FROM accounts';

        return DB::getResults($query, __FUNCTION__);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param bool $isMassive para no actualizar el histórico ni enviar mensajes
     * @param bool $isRestore indica si es una restauración
     * @return bool
     */
    public function updateAccountPass($isMassive = false, $isRestore = false)
    {
        $message['action'] = __FUNCTION__;

        // No actualizar el histórico si es por cambio de clave maestra o restauración
        if (!$isMassive && !$isRestore) {
            // Guardamos una copia de la cuenta en el histórico
            if (!$this->addHistory($this->accountId, $this->accountUserEditId, false)) {
                $message['text'][] = _('Error al actualizar el historial');
                SP_Log::wrLogInfo($message);
                return false;
            }
        }

        $query = 'UPDATE accounts SET '
            . 'account_pass = :accountPass,'
            . 'account_IV = :accountIV,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_dateEdit = NOW() '
            . 'WHERE account_id = :accountId';

        $data['accountPass'] = $this->accountPass;
        $data['accountIV'] = $this->accountIV;
        $data['accountUserEditId'] = $this->accountUserEditId;
        $data['accountId'] = $this->accountId;


        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        // No escribir en el log ni enviar correos si la actualización es
        // por cambio de clave maestra o restauración
        if (!$isMassive && !$isRestore) {
            $accountInfo = array('customer_name', 'account_name');
            $this->getAccountInfoById($accountInfo);

            $message['action'] = _('Modificar Clave');
            $message['text'][] = SP_Html::strongText(_('Cliente') . ': ') . $this->cacheParams['customer_name'];
            $message['text'][] = SP_Html::strongText(_('Cuenta') . ': ') . $this->cacheParams['account_name'] . " ($this->accountId)";

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);
        }

        return true;
    }

    /**
     * Actualiza las claves de todas las cuentas en el histórico con la nueva clave maestra.
     *
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass     con la nueva clave maestra
     * @param string $newHash           con el nuevo hash de la clave maestra
     * @return bool
     */
    public function updateAllAccountsHistoryMPass($currentMasterPass, $newMasterPass, $newHash)
    {
        $idOk = array();
        $errorCount = 0;
        $demoEnabled = SP_Util::demoIsEnabled();

        $message['action'] = _('Actualizar Clave Maestra (H)');
        $message['text'][] = _('Inicio');

        SP_Log::wrLogInfo($message);

        // Limpiar 'text' para los próximos mensajes
        $message['text'] = array();

        if (!SP_Crypt::checkCryptModule()) {
            $message['text'][] = _('Error en el módulo de encriptación');
            SP_Log::wrLogInfo($message);
            return false;
        }

        $accountsPass = $this->getAccountsHistoryPassData();

        if (!$accountsPass) {
            $message['text'][] = _('Error al obtener las claves de las cuentas');
            SP_Log::wrLogInfo($message);
            return false;
        }

        foreach ($accountsPass as $account) {
            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $idOk[] = $account->acchistory_id;
                continue;
            }

            if (!$this->checkAccountMPass($account->acchistory_id)) {
                $errorCount++;
                $message['text'][] = _('La clave maestra del registro no coincide') . ' (' . $account->acchistory_id . ')';
                continue;
            }

            if (strlen($account->acchistory_IV) < 32){
                $errorCount++;
                $message['text'][] = _('IV de encriptación incorrecto') . ' (' . $account->acchistory_id . ')';
                continue;
            }

            $decryptedPass = SP_Crypt::getDecrypt($account->acchistory_pass, $currentMasterPass, $account->acchistory_IV);

            $this->accountPass = SP_Crypt::mkEncrypt($decryptedPass, $newMasterPass);
            $this->accountIV = SP_Crypt::$strInitialVector;

            if ($this->accountPass === false) {
                $errorCount++;
                continue;
            }

            if (!$this->updateAccountHistoryPass($account->acchistory_id, $newHash)) {
                $errorCount++;
                $message['text'][] = _('Fallo al actualizar la clave del histórico') . ' (' . $account->acchistory_id . ')';
                continue;
            }

            $idOk[] = $account->acchistory_id;
        }

        // Vaciar el array de mensaje de log
        if (count($message['text']) > 0) {
            SP_Log::wrLogInfo($message);
            $message['text'] = array();
        }

        if ($idOk) {
            $message['text'][] = _('Registros actualizados') . ': ' . implode(',', $idOk);
            SP_Log::wrLogInfo($message);
            $message['text'] = array();
        }

        $message['text'][] = _('Fin');
        SP_Log::wrLogInfo($message);

        if ($errorCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos relativo a la clave de todas las cuentas del histórico.
     *
     * @return false|array con los datos de la clave
     */
    private function getAccountsHistoryPassData()
    {
        $query = 'SELECT acchistory_id, acchistory_pass, acchistory_IV FROM accHistory';

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__);
    }

    /**
     * Comprueba el hash de la clave maestra del registro de histórico de una cuenta.
     *
     * @param int $id opcional, con el Id del registro a comprobar
     * @return bool
     */
    public function checkAccountMPass($id = null)
    {
        $query = 'SELECT acchistory_mPassHash ' .
            'FROM accHistory ' .
            'WHERE acchistory_id = :id AND acchistory_mPassHash = :mPassHash';

        $data['id'] = (is_null($id)) ? $this->accountId : $id;
        $data['mPassHash'] = SP_Config::getConfigValue('masterPwd');

        return (DB::getResults($query, __FUNCTION__, $data) !== false);
    }

    /**
     * Actualiza la clave del histórico de una cuenta en la BBDD.
     *
     * @param int $id         con el id del registro a actualizar
     * @param string $newHash con el hash de la clave maestra
     * @return bool
     */
    public function updateAccountHistoryPass($id, $newHash)
    {
        $query = 'UPDATE accHistory SET '
            . 'acchistory_pass = :accountPass,'
            . 'acchistory_IV = :accountIV,'
            . 'acchistory_mPassHash = :newHash '
            . 'WHERE acchistory_id = :id';

        $data['accountPass'] = $this->accountPass;
        $data['accountIV'] = $this->accountIV;
        $data['newHash'] = $newHash;
        $data['id'] = $id;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Calcular el hash de los datos de una cuenta.
     * Esta función se utiliza para verificar si los datos de un formulario han sido cambiados
     * con respecto a los guardados
     *
     * @return string con el hash
     */
    public function calcChangesHash()
    {
        $groups = 0;
        $users = 0;

        if (is_array($this->accountUserGroupsId)) {
            $groups = implode($this->accountUserGroupsId);
        } elseif (is_array($this->accountCacheUserGroupsId)) {
            foreach ($this->accountCacheUserGroupsId as $group) {
                if (is_array($group)) {
                    // Ordenar el array para que el hash sea igual
                    sort($group, SORT_NUMERIC);
                    $groups = implode($group);
                }
            }
        }

        if (is_array($this->accountUsersId)) {
            $users = implode($this->accountUsersId);
        } elseif (is_array($this->accountCacheUsersId)) {
            foreach ($this->accountCacheUsersId as $user) {
                if (is_array($user)) {
                    // Ordenar el array para que el hash sea igual
                    sort($user, SORT_NUMERIC);
                    $users = implode($user);
                }
            }
        }

        if (!empty($this->accountModHash)) {
            $hashItems = $this->accountModHash . (int)$users . (int)$groups;
            //error_log("HASH MySQL: ".$hashItems);
        } else {
            $hashItems = $this->accountName .
                $this->accountCategoryId .
                $this->accountCustomerId .
                $this->accountLogin .
                $this->accountUrl .
                $this->accountNotes .
                $this->accountOtherUserEdit .
                $this->accountOtherGroupEdit .
                (int)$users .
                (int)$groups;
            //error_log("HASH PHP: ".$hashItems);
        }

        return md5($hashItems);
    }

    /**
     * Devolver datos de la cuenta para comprobación de accesos.
     *
     * @param int $accountId con el id de la cuenta
     * @return array con los datos de la cuenta
     */
    public function getAccountDataForACL($accountId = null)
    {
        $accId = (!is_null($accountId)) ? $accountId : $this->accountId;

        return array(
            'id' => $accId,
            'user_id' => $this->accountUserId,
            'group_id' => $this->accountUserGroupId,
            'users_id' => $this->getUsersAccount(),
            'groups_id' => $this->getGroupsAccount(),
            'otheruser_edit' => $this->accountOtherUserEdit,
            'othergroup_edit' => $this->accountOtherGroupEdit
        );
    }

    /**
     * Obtiene el listado usuarios con acceso a una cuenta.
     * Lo almacena en la cache de sesión como array de cuentas
     *
     * @return array Con los registros con id de cuenta como clave e id de usuario como valor
     */
    public function getUsersAccount()
    {
        $accId = ($this->accountIsHistory && $this->accountParentId) ? $this->accountParentId : $this->accountId;

        $cacheUsers = &$_SESSION['cache']['usersId'];

        if (!is_array($cacheUsers)) {
            $cacheUsers = array($accId => array(), 'expires' => 0);
        }

        if (!isset($cacheUsers[$accId])
            || time() > $cacheUsers['expires'])
        {
            $cacheUsers[$accId] = SP_Users::getUsersForAccount($accId);
            $cacheUsers['expires'] = time() + 300;
        }

        return $cacheUsers[$accId];
    }

    /**
     * Obtiene el listado de grupos secundarios de una cuenta.
     * Lo almacena en la cache de sesión como array de cuentas
     *
     * @return array con los registros con id de cuenta como clave e id de grupo como valor
     */
    public function getGroupsAccount()
    {
        $accId = ($this->accountIsHistory && $this->accountParentId) ? $this->accountParentId : $this->accountId;

        $cacheUserGroups = &$_SESSION['cache']['userGroupsId'];

        if (!is_array($cacheUserGroups)) {
            //error_log('Groups cache NO_INIT');
            $cacheUserGroups = array($accId => array(), 'expires' => 0);
        }

        if (!isset($cacheUserGroups[$accId])
            || time() > $cacheUserGroups['expires'])
        {
            $cacheUserGroups[$accId] = SP_Groups::getGroupsForAccount($accId);
            $cacheUserGroups['expires'] = time() + 300;
        }

        return $cacheUserGroups[$accId];
    }
}