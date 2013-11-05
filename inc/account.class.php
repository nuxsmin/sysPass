<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Account {

    // Variables con info de la cuenta
    var $accountId;
    var $accountName;
    var $accountCustomerId;
    var $accountCustomerName;
    var $accountCategoryId;
    var $accountCategoryName;
    var $accountLogin;
    var $accountUrl;
    var $accountPass;
    var $accountIV;
    var $accountNotes;
    var $accountNumView;
    var $accountNumViewDecrypt;
    var $accountDateAdd;
    var $accountDateEdit;

    // Variables con info del usuario de la Cuenta
    var $accountUserName;
    var $accountUserId;
    var $accountUserGroupName;
    var $accountUserGroupId;
    var $accountUserEditName;
    var $accountUserEditId;
    var $accountUserGroupsId;

    // Variables de última acción
    var $lastAction;
    var $lastId;
    var $accountParentId;

    // Variable de consulta
    var $queryNumRows;
    // Variable para indicar si la cuenta es desde el histórico
    var $accountIsHistory = 0;
    // Cache para grupos de usuarios de las cuentas
    var $accountCacheUserGroupsId;
    
    // Variables para filtros de búsqueda
    static $accountSearchTxt;
    static $accountSearchCustomer;
    static $accountSearchCategory;
    static $accountSearchOrder;
    static $accountSearchKey;
	
    // Variable para la caché de parámetros
    var $cacheParams;

    /**
     * @brief Obtener las cuentas de una búsqueda
     * @param array $searchFilter filtros de búsqueda
     * @return array resultado de la consulta
     */ 
    public function getAccounts($searchFilter){
        switch ($searchFilter["keyId"]){
            case 1:
                $orderKey = "account_name";
                break;
            case 2:
                $orderKey = "category_name";
                break;
            case 3:
                $orderKey = "account_login";
                break;
            case 4:
                $orderKey = "account_url";
                break;
            case 5:
                $orderKey = "account_customerId";
                break;
            default :
                $orderKey = "customer_name, account_name";
                break;
        }

        $querySelect = "SELECT SQL_CALC_FOUND_ROWS DISTINCT account_id, account_customerId, category_name, account_name,
                        account_login, account_url, account_notes, account_userId, account_userGroupId, usergroup_name, customer_name
                        FROM accounts
                        LEFT JOIN categories ON account_categoryId = category_id
                        LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id
                        LEFT JOIN customers ON customer_id = account_customerId
                        LEFT JOIN accGroups ON accgroup_accountId = account_id";

        $queryCount = "SELECT COUNT(DISTINCT account_id) AS Number FROM accounts
                        LEFT JOIN accGroups ON accgroup_accountId = account_id";

        $arrFilterCommon = array();
        $arrFilterSelect= array();
        $arrFilterUser = array();
        $arrQueryWhere = array();
        
        if ( $searchFilter["txtSearch"] ){
            $arrFilterCommon[] = "account_name LIKE '%".$searchFilter["txtSearch"]."%'";
            $arrFilterCommon[] = "account_login LIKE '%".$searchFilter["txtSearch"]."%'";
            $arrFilterCommon[] = "account_url LIKE '%".$searchFilter["txtSearch"]."%'";
            $arrFilterCommon[] = "account_notes LIKE '%".$searchFilter["txtSearch"]."%'";
        }
        
        if ( $searchFilter["categoryId"] != 0 ){
            $arrFilterSelect[] = "category_id = ".$searchFilter["categoryId"];
        }
        if ( $searchFilter["customerId"] != 0 ){
            $arrFilterSelect[] = "account_customerId = '".$searchFilter["customerId"]."'";
        }
        
        
        if ( count($arrFilterCommon) > 0 ){
            $arrQueryWhere[] =  "(".implode(" OR ", $arrFilterCommon).")";
        }
        if ( count($arrFilterSelect) > 0 ){
            $arrQueryWhere[] =  "(".implode(" AND ", $arrFilterSelect).")";
        }
        
        if ( ! $_SESSION["uisadminapp"] && ! $_SESSION["uisadminacc"] ) {
            $arrFilterUser[] = "account_userGroupId = ".$searchFilter["groupId"];
            $arrFilterUser[] = "account_userId = ".$searchFilter["userId"];
            $arrFilterUser[] = "accgroup_groupId = ".$searchFilter["groupId"];

            $arrQueryWhere[] = "(".implode(" OR ", $arrFilterUser).")";
        }
        
        $order = ( $searchFilter["txtOrder"] == 0 ) ? 'ASC' : 'DESC';
        
        $queryOrder = " ORDER BY $orderKey ".$order;

        if ( $searchFilter["limitCount"] != 99 ) {
            $queryLimit = "LIMIT ".$searchFilter["limitStart"].", ".$searchFilter["limitCount"];
        }

        if ( count($arrQueryWhere) === 1 ){
            $query = $querySelect." WHERE ".implode("", $arrQueryWhere)." ".$queryOrder." ".$queryLimit;
        } elseif ( count($arrQueryWhere) > 1 ){
            $query = $querySelect." WHERE ".implode(" AND ", $arrQueryWhere)." ".$queryOrder." ".$queryLimit;
        } else{
            $query = $querySelect.$queryOrder." ".$queryLimit;
        }
        
        $queryCount = $queryCount." WHERE ".implode(" AND ", $arrQueryWhere);
        
        // Consulta de la búsqueda de cuentas
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE ){
            return FALSE;
        }

        //error_log($query);
        
        // Obtenemos el número de registros totales de la consulta sin contar el LIMIT
        $resQueryNumRows = DB::getResults("SELECT FOUND_ROWS() as numRows", __FUNCTION__);
        $this->queryNumRows =  $resQueryNumRows[0]->numRows;

        $_SESSION["accountSearchTxt"] = $searchFilter["txtSearch"];
        $_SESSION["accountSearchCustomer"] = $searchFilter["customerId"];
        $_SESSION["accountSearchCategory"] = $searchFilter["categoryId"];
        $_SESSION["accountSearchOrder"] = $searchFilter["txtOrder"];
        $_SESSION["accountSearchKey"] = $searchFilter["keyId"];
        
        return $queryRes;
    }

    /**
     * @brief Obtener los datos de una cuenta
     * @return none
     * 
     * Esta funcion realiza la consulta a la BBDD y guarda los datos en las variables de la clase.
     */ 
    public function getAccount(){
        $query = "SELECT account_id, account_name, account_categoryId, account_userId, account_customerId,
                    account_userGroupId, account_userEditId, category_name, account_login, account_url, account_pass,
                    account_IV, account_notes, account_countView, account_countDecrypt,
                    account_dateAdd, account_dateEdit, u1.user_name as user_name, u2.user_name as user_editName,
                    usergroup_name, customer_name
                    FROM accounts
                    LEFT JOIN categories ON account_categoryId = category_id
                    LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id
                    LEFT JOIN usrData u1 ON account_userId = u1.user_id
                    LEFT JOIN usrData u2 ON account_userEditId = u2.user_id
                    LEFT JOIN customers ON account_customerId = customer_id
                    WHERE account_id = ".(int)$this->accountId." LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ) return FALSE;

        // El resultado es un array de objetos
        $account = $queryRes[0];

        $this->accountName = $account->account_name;
        $this->accountCategoryId = $account->account_categoryId;
        $this->accountUserId = $account->account_userId;
        $this->accountCustomerId = $account->account_customerId;
        $this->accountCustomerName = $account->customer_name;
        $this->accountUserGroupId = $account->account_userGroupId;
        $this->accountUserEditId = $account->account_userEditId;
        $this->accountCategoryName = $account->category_name;
        $this->accountLogin = $account->account_login;
        $this->accountUrl = $account->account_url;
        $this->accountPass = $account->account_pass;
        $this->accountIV = $account->account_IV;
        $this->accountNotes = $account->account_notes;
        $this->accountNumView = $account->account_countView;
        $this->accountNumViewDecrypt = $account->account_countDecrypt;
        $this->accountDateAdd = $account->account_dateAdd;
        $this->accountDateEdit = ( $account->account_dateEdit != "0000-00-00 00:00:00") ? $account->account_dateEdit : '';
        $this->accountUserName = $account->user_name;
        $this->accountUserGroupName = $account->usergroup_name;
        $this->accountUserEditName = ( $account->user_editName ) ? $account->user_editName : "";
    }

    /**
     * @brief Obtener los datos del histórico de una cuenta
     * @return none
     * 
     * Esta funcion realiza la consulta a la BBDD y guarda los datos del histórico en las variables de la clase.
     */ 
    public function getAccountHistory(){
        $query = "SELECT acchistory_accountId, acchistory_customerId, acchistory_categoryId, acchistory_name, acchistory_login,
                    acchistory_url, acchistory_pass, acchistory_IV, acchistory_notes, acchistory_countView,
                    acchistory_countDecrypt, acchistory_dateAdd, acchistory_dateEdit, acchistory_userId, acchistory_userGroupId,
                    acchistory_userEditId, acchistory_isModify, acchistory_isDeleted, u1.user_name, usergroup_name,
                    u2.user_name as user_editName, category_name, customer_name
                    FROM accHistory
                    LEFT JOIN categories ON acchistory_categoryId = category_id
                    LEFT JOIN usrGroups ON acchistory_userGroupId = usergroup_id
                    LEFT JOIN usrData u1 ON acchistory_userId = u1.user_id
                    LEFT JOIN usrData u2 ON acchistory_userEditId = u2.user_id
                    LEFT JOIN customers ON acchistory_customerId = customer_id
                    WHERE acchistory_id = ".(int)$this->accountId." LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ) return FALSE;

        // El resultado es un array de objetos
        $account = $queryRes[0];

        $this->accountCustomerId = $account->acchistory_customerId;
        $this->accountCustomerName = $account->customer_name;
        $this->accountCategoryId = $account->acchistory_categoryId;
        $this->accountUserId = $account->acchistory_userId;
        $this->accountUserGroupId = $account->acchistory_userGroupId;
        $this->accountUserEditId = $account->acchistory_userEditId;
        $this->accountName = $account->acchistory_name;
        $this->accountCategoryName = $account->category_name;
        $this->accountLogin = $account->acchistory_login;
        $this->accountUrl = $account->acchistory_url;
        $this->accountPass = $account->acchistory_pass;
        $this->accountIV = $account->acchistory_IV;
        $this->accountNotes = $account->acchistory_notes;
        $this->accountNumView = $account->acchistory_countView;
        $this->accountNumViewDecrypt = $account->acchistory_countDecrypt;
        $this->accountDateAdd = $account->acchistory_dateAdd;
        $this->accountDateEdit = $account->acchistory_dateEdit;
        $this->accountUserName = $account->user_name;
        $this->accountUserGroupName = $account->usergroup_name;
        $this->accountUserEditName = $account->user_editName;
    }

    /**
     * @brief Actualiza los datos de una cuenta en la BBDD
     * @return bool
     */ 
    public function updateAccount(){
        $message['action'][] = __FUNCTION__;
        
        // Guardamos una copia de la cuenta en el histórico
        if ( ! $this->addHistory($this->accountId, $this->accountUserEditId, FALSE) ){
            $message['text'][] = _('Error al actualizar el historial');
            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        if ( ! $this->updateAccGroups() ){
            $message['text'][] = _('Error al actualizar los grupos secundarios');
            SP_Common::wrLogInfo($message);
        }

        $query = "UPDATE accounts SET
                    account_customerId = '".DB::escape($this->accountCustomerId)."',
                    account_categoryId = ".(int)$this->accountCategoryId.",
                    account_name = '".DB::escape($this->accountName)."',
                    account_login = '".DB::escape($this->accountLogin)."',
                    account_url = '".DB::escape($this->accountUrl)."',
                    account_notes = '".DB::escape($this->accountNotes)."',
                    account_userEditId = ".(int)$this->accountUserEditId.",
                    account_dateEdit = NOW()
                    WHERE account_id = ".(int)$this->accountId;

        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);
		
        $message['action'] = _('Cuenta actualizada');
        $message['text'][] = _('Cliente').": ".$this->cacheParams['customer_name'];
        $message['text'][] = _('Cuenta').": $this->accountName ($this->accountId)";
                
        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);
        
        return TRUE;
    }

    /**
     * @brief Actualiza la clave de una cuenta en la BBDD
     * @param bool $isMasive para no actualizar el histórico ni enviar mensajes
     * @return bool
     */ 
    public function updateAccountPass($isMassive = FALSE){
        $message['action'] = __FUNCTION__;
        
        // No actualizar el histórico si es por cambio de clave maestra
        if ( ! $isMassive ){
            // Guardamos una copia de la cuenta en el histórico
            if ( ! $this->addHistory($this->accountId, $this->accountUserEditId, FALSE) ){
                $message['text'][] = _('Error al actualizar el historial');
                SP_Common::wrLogInfo($message);
                return FALSE;
            }
        }

        $query = "UPDATE accounts SET
                        account_pass = '".DB::escape($this->accountPass)."',
                        account_IV = '".DB::escape($this->accountIV)."',
                        account_userEditId = ".(int)$this->accountUserEditId.",
                        account_dateEdit = NOW()
                        WHERE account_id = ".(int)$this->accountId;

        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        // No escribir en el log ni enviar correos si la actualización es 
        // por cambio de clave maestra...
        if ( ! $isMassive ){
            $accountInfo = array('customer_name','account_name');
            $this->getAccountInfoById($accountInfo);

            $message['action'] = _('Modificar Clave');
            $message['text'][] = _('Cliente').": ".$this->cacheParams['customer_name'];
            $message['text'][] = _('Cuenta').": ".$this->cacheParams['account_name']." ($this->accountId)";

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);
        }
		
        return TRUE;
    }

    /**
     * @brief Actualiza la clave del histórico de una cuenta en la BBDD
     * @param int $id con el id del registro a actualizar
     * @param string $newHash con el hash de la clave maestra
     * @return bool
     */ 
    public function updateAccountHistoryPass($id, $newHash){
        $query = "UPDATE accHistory SET
                        acchistory_pass = '".DB::escape($this->accountPass)."',
                        acchistory_IV = '".DB::escape($this->accountIV)."',
                        acchistory_mPassHash = '" . DB::escape($newHash)."'
                        WHERE acchistory_id = ".(int)$id;

        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        return TRUE;
    }
    
    /**
     * @brief Crea una nueva cuenta en la BBDD
     * @return bool
     */ 
    public function createAccount(){
        $query = "INSERT INTO accounts SET
                    account_customerId = ".(int)$this->accountCustomerId.",
                    account_categoryId = ".(int)$this->accountCategoryId.",
                    account_name = '".DB::escape($this->accountName)."',
                    account_login = '".DB::escape($this->accountLogin)."',
                    account_url = '".DB::escape($this->accountUrl)."',
                    account_pass = '$this->accountPass',
                    account_IV = '".DB::escape($this->accountIV)."',
                    account_notes = '".DB::escape($this->accountNotes)."',
                    account_dateAdd = NOW(),
                    account_userId = ".(int)$this->accountUserId.",
                    account_userGroupId = ".(int)$this->accountUserGroupId;

        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        $this->accountId = DB::$lastId;

        $message['action'] = __FUNCTION__;
        
        if ( ! $this->updateAccGroups() ){
            $message['text'][] = _('Error al actualizar los grupos secundarios');
            SP_Common::wrLogInfo($message);
            $message['text'] = array();
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);
		
        $message['action'] = _('Nueva Cuenta');
        $message['text'][] = _('Cliente').": ".$this->cacheParams['customer_name'];
        $message['text'][] = _('Cuenta').": $this->accountName ($this->accountId)";
                
        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);
        
        return TRUE;
    }

    /**
     * @brief Elimina los datos de una cuenta en la BBDD
     * @return bool
     */ 
    public function deleteAccount(){
        // Guardamos una copia de la cuenta en el histórico
        $this->addHistory(TRUE) || die (_('ERROR: Error en la operación.'));

        $accountInfo = array('account_name,customer_name');
        $this->getAccountInfoById($accountInfo);
        
        $message['action'] = _('Eliminar Cuenta');
        $message['text'][] = _('Cliente').": ".$this->cacheParams['customer_name'];
        $message['text'][] = _('Cuenta').": ".$this->cacheParams['account_name']." ($this->accountId)";
        
        $query = "DELETE FROM accounts "
                . "WHERE account_id = ".(int)$this->accountId." LIMIT 1";
        
        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }
                
        SP_Common::wrLogInfo($message);

        if ( ! $this->deleteAccountGroups() ){
            return FALSE;
        }
        
        if ( ! $this->deleteAccountFiles() ){
            return FALSE;
        }
        
        SP_Common::sendEmail($message);
            
        return TRUE;
    }
    
    /**
     * @brief Elimina los grupos secundarios de una cuenta en la BBDD
     * @return bool
     */ 
    private function deleteAccountGroups(){
        $query = "DELETE FROM accGroups "
                . "WHERE accgroup_accountId = ".(int)$this->accountId;
        
        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        return TRUE;
    }
    
    /**
     * @brief Elimina los archivos de una cuenta en la BBDD
     * @return bool
     */ 
    private function deleteAccountFiles(){
        $query = "DELETE FROM accFiles "
                . "WHERE accfile_accountId = ".(int)$this->accountId;
        
        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }
        
        return TRUE;
    }

    /**
     * @brief Actualiza los grupos secundarios de una cuenta en la BBDD
     * @return bool
     */ 
    private function updateAccGroups(){
        $valuesDel = "";
        $valuesNew = "";
        $queryDel = "";
        $query = "";

        $accOldUGroups = $this->getGroupsAccount();
        $accNewUGroups = $this->accountUserGroupsId;

        if ( is_array($accOldUGroups) && ! is_array($accNewUGroups) ){
            $queryDel = "DELETE FROM accGroups "
                    . "WHERE accgroup_accountId = ".(int)$this->accountId;
        } else if ( is_array($accNewUGroups) ){
            if ( ! is_array($accOldUGroups) ){
                // Obtenemos los grupos a añadir
                foreach ( $accNewUGroups as $userNewGroupId ){
                    $valuesNew .= "(".(int)$this->accountId.",".$userNewGroupId."),";
                }
            } else {
                // Obtenemos los grupos a añadir a partir de los existentes
                foreach ( $accNewUGroups as $userNewGroupId ){
                    if ( ! in_array($userNewGroupId, $accOldUGroups)){
                        $valuesNew .= "(".(int)$this->accountId.",".$userNewGroupId."),";
                    }
                }

                // Obtenemos los grupos a eliminar
                foreach ( $accOldUGroups as $userOldGroupId ){
                    if ( ! in_array($userOldGroupId, $accNewUGroups)){
                        $valuesDel[] = $userOldGroupId;
                    }
                }

                if ( is_array($valuesDel) ){
                    $queryDel = "DELETE FROM accGroups "
                            . "WHERE accgroup_accountId = ".(int)$this->accountId." AND (";
                    $numValues = count($valuesDel);
                    $i = 0;

                    foreach ($valuesDel as $value){
                        if ( $i == $numValues - 1 ){
                            $queryDel .= "accgroup_groupId = $value";
                        } else {
                            $queryDel .= "accgroup_groupId = $value OR ";
                        }
                        $i++;
                    }
                    $queryDel .= ")";
                }
            }

            if ( $valuesNew ){
                $query = "INSERT INTO accGroups (accgroup_accountId, accgroup_groupId) VALUES ".rtrim($valuesNew, ",");
            }
        }

        if ( $queryDel ){
            if ( DB::doQuery($queryDel, __FUNCTION__) === FALSE ){
                return FALSE;
            }
        }

        if ( $query ){
            if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * @brief Crear un nuevo resitro de histório de cuenta en la BBDD
     * @return bool
     */ 
    private function addHistory ($isDelete){
        $objAccountHist = new SP_Account;

        $isModify = 0;

        $objAccountHist->accountId = $this->accountId;
        $objAccountHist->getAccount();

        if ( $isDelete == FALSE ){
            $isModify = 1;
            $isDelete = 0;
        } else {
            $isDelete = 1;
        }

        $query = "INSERT INTO accHistory SET
                    acchistory_accountId = " . $objAccountHist->accountId . ",
                    acchistory_customerId = " . $objAccountHist->accountCustomerId . ",
                    acchistory_categoryId = " . $objAccountHist->accountCategoryId . ",
                    acchistory_name = '" . DB::escape($objAccountHist->accountName) . "',
                    acchistory_login = '" . DB::escape($objAccountHist->accountLogin) . "',
                    acchistory_url = '" . DB::escape($objAccountHist->accountUrl) . "',
                    acchistory_pass = '" . DB::escape($objAccountHist->accountPass) . "',
                    acchistory_IV = '" . DB::escape($objAccountHist->accountIV) . "',
                    acchistory_notes = '" . DB::escape($objAccountHist->accountNotes) . "',
                    acchistory_countView = " . $objAccountHist->accountNumView . ",
                    acchistory_countDecrypt = " . $objAccountHist->accountNumViewDecrypt . ",
                    acchistory_dateAdd = '" . $objAccountHist->accountDateAdd . "',
                    acchistory_dateEdit = '" . $objAccountHist->accountDateEdit . "',
                    acchistory_userId = " . $objAccountHist->accountUserId . ",
                    acchistory_userGroupId = " . $objAccountHist->accountUserGroupId . ",
                    acchistory_userEditId = " . $objAccountHist->accountUserEditId . ",
                    acchistory_isModify = " . $isModify . ",
                    acchistory_isDeleted = " . $isDelete . ",
                    acchistory_mPassHash = '" . DB::escape(SP_Config::getConfigValue('masterPwd'))."'";

        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Obtiene el listado del histórico de una cuenta
     * @return array con los registros con id como clave y fecha - usuario como valor
     */ 
    public function getAccountHistoryList(){
        $query = "SELECT acchistory_id, acchistory_dateEdit, u1.user_login as user_edit, u2.user_login as user_add, acchistory_dateAdd
                    FROM accHistory
                    LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id
                    LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id
                    WHERE acchistory_accountId = ".$_SESSION["accParentId"]." 
                    ORDER BY acchistory_id DESC";
        
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }

        $arrHistory = array();

        foreach ( $queryRes as $history ){
            if ( $history->acchistory_dateEdit == '0000-00-00 00:00:00' ){
                $arrHistory[$history->acchistory_id] = $history->acchistory_dateAdd." - ".$history->user_add;
            } else {
                $arrHistory[$history->acchistory_id] = $history->acchistory_dateEdit." - ".$history->user_edit;
            }
        }

        return $arrHistory;
    }

    /**
     * @brief Obtiene el listado de grupos secundarios de una cuenta
     * @return array con los registros con id de cuenta como clave e id de grupo como valor
     */ 
    public function getGroupsAccount (){
        $accId = ( $this->accountIsHistory && $this->accountParentId ) ? $this->accountParentId : $this->accountId;
        
        if ( ! is_array($this->accountCacheUserGroupsId) ){
            //error_log('Groups cache MISS');
            $this->accountCacheUserGroupsId = array($accId => array());
        } else{
            if ( array_key_exists($accId, $this->accountCacheUserGroupsId) ){
                //error_log('Groups cache HIT');
                return $this->accountCacheUserGroupsId[$accId];
            }
        }
        
        $query = "SELECT accgroup_groupId FROM accGroups "
                . "WHERE accgroup_accountId = ".(int)$accId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE && ! is_array($queryRes) ){
            return FALSE;
        }

        if ( ! is_array($queryRes) ) return array();
        
        foreach ( $queryRes as $groups ){
            $this->accountCacheUserGroupsId[$accId][] = $groups->accgroup_groupId;
        }

        return $this->accountCacheUserGroupsId[$accId];
    }

    /**
     * @brief Obtiene el listado de grupos secundarios
     * @return array con los registros con nombre de grupo como clave e id de grupo como valor
     */ 
    public static function getSecGroups(){
        $query = "SELECT usergroup_id, usergroup_name FROM usrGroups";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }

        foreach ( $queryRes as $groups ){
            $arrGroups[$groups->usergroup_name] = $groups->usergroup_id;
        }

        return $arrGroups;
    }
    
    /**
     * @brief Obtiene el listado con el nombre de grupos secundarios de una cuenta
     * @return array con los nombres de los grupos ordenados
     */ 
    public static function getAccountGroupsName ($accountId){
        $query = "SELECT usergroup_name FROM accGroups "
                . "JOIN usrGroups ON accgroup_groupId = usergroup_id "
                . "WHERE accgroup_accountId = ".(int)$accountId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE && ! is_array($queryRes) ){
            return FALSE;
        }

        if (!is_array($queryRes)) {
            return FALSE;
        }

        foreach ( $queryRes as $groups ){
            $groupsName[] = $groups->usergroup_name;
        }
        
        sort($groupsName, SORT_STRING);
        
        return $groupsName;
    }

    /**
     * @brief Incrementa el contador de visitas de una cuenta en la BBDD
     * @return bool
     */ 
    public function incrementViewCounter(){
        $query = "UPDATE accounts SET account_countView = (account_countView + 1) "
                . "WHERE account_id = ".(int)$this->accountId;
        
        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Incrementa el contador de vista de clave de una cuenta en la BBDD
     * @return bool
     */ 
    public function incrementDecryptCounter(){
        $query = "UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) "
                . "WHERE account_id = ".(int)$this->accountId;
        
        if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Obtiene el número de cuentas que un usuario puede ver
     * @return int con el número de registros
     */ 
    public function getAccountMax(){
        $userGroupId = $_SESSION["ugroup"];
        $userId = $_SESSION["uid"];
        $userIsAdminApp = $_SESSION['uisadminapp'];
        $userIsAdminAcc = $_SESSION['uisadminacc'];

        if ( ! $userIsAdminApp && ! $userIsAdminAcc ){
            $query = "SELECT COUNT(DISTINCT account_id) as numacc FROM accounts
                        LEFT JOIN accGroups ON account_id = accgroup_accountId
                        WHERE account_userGroupId = ".(int)$userGroupId."
                        OR account_userId = ".(int)$userId."
                        OR accgroup_groupId = ".(int)$userGroupId;
        } else {
            $query = "SELECT COUNT(account_id) as numacc FROM accounts";
        }

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }

        return $queryRes[0]->numacc;
    }

    /**
     * @brief Comprueba los permisos de acceso a una cuenta
     * @param string $action con la acción realizada
     * @param int $accountUserId opcional, id del usuario a verificar
     * @param int $accountId opcional, id de la cuenta a verificar
     * @param int $accountUserGroupId opcional, id con el grupo del usuario a verificar
     * @return bool
     */ 
    public function checkAccountAccess($action, $accountUserId = "", $accountId = "", $accountUserGroupId = ""){
        $userGroupId = $_SESSION["ugroup"];
        $userId = $_SESSION["uid"];
        $userIsAdminApp = $_SESSION["uisadminapp"];
        $userIsAdminAcc = $_SESSION["uisadminacc"];
        
        // Convertimos en array la lista de grupos de la cuenta
        if ( ! $this->accountId && ! $accountId ){
            return FALSE;
        }
        
        if ( $accountId ) {
            $this->accountId = $accountId;
        } 

        $accountUserGroups = $this->getGroupsAccount();
        
        $accountUserGroupId = ( ! $accountUserGroupId )  ? $this->accountUserGroupId: $accountUserGroupId;
        $accountUserId = ( ! $accountUserId ) ? $this->accountUserId : $accountUserId;

        switch ($action){
            case "accview":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || in_array($userGroupId, $accountUserGroups)
                        || $userIsAdminApp || $userIsAdminAcc){
                    return TRUE;
                }
                break;
            case "accviewpass":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || in_array($userGroupId, $accountUserGroups)
                        || $userIsAdminApp || $userIsAdminAcc ){
                    return TRUE;
                }
                break;
            case "accviewhistory":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || in_array($userGroupId, $accountUserGroups)
                        || $userIsAdminApp || $userIsAdminAcc){
                    return TRUE;
                }
                break;
            case "accedit":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || $userIsAdminApp || $userIsAdminAcc){
                    return TRUE;
                }
                break;
            case "accdelete":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || $userIsAdminApp || $userIsAdminAcc ){
                    return TRUE;
                }
                break;
            case "acceditpass":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || $userIsAdminApp || $userIsAdminAcc){
                    return TRUE;
                }
                break;
            case "acccopy":
                if ( $userId == $accountUserId 
                        || $userGroupId == $accountUserGroupId
                        || in_array($userGroupId, $accountUserGroups)
                        || $userIsAdminApp || $userIsAdminAcc){
                    return TRUE;
                }
                break;
        }
               
        return FALSE;
    }

    /**
     * @brief Obtener los datos relativos a la clave de todas las cuentas
     * @return array con los datos de la clave
     */
    private function getAccountsPassData(){
        $query = "SELECT account_id, account_pass, account_IV FROM accounts";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }
        
        return $queryRes;
    }
    
    /**
     * @brief Obtener los datos relativo a la clave de todas las cuentas del histórico
     * @return array con los datos de la clave
     */
    private function getAccountsHistoryPassData(){
        $query = "SELECT acchistory_id, acchistory_pass, acchistory_IV FROM accHistory";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }
        
        return $queryRes;
    }
    
    /**
     * @brief Actualiza las claves de todas las cuentas con la nueva clave maestra
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass con la nueva clave maestra
     * @return bool
     */
    public function updateAllAccountsMPass($currentMasterPass, $newMasterPass){
        $accountsOk = array();
        $userId = $_SESSION["uid"];
        $errorCount = 0;
        $demoEnabled = SP_Config::getValue('demoenabled',0);

        $message['action'] = _('Actualizar Clave Maestra');
        $message['text'][] = _('Inicio');
        
        SP_Common::wrLogInfo($message);
        
        // Limpiar 'text' para los próximos mensajes
        $message['text'] = array();
       
        $crypt = new SP_Crypt();
        
        if ( !SP_Crypt::checkCryptModule() ) {
            $message['text'][] = _('Error en el módulo de encriptación');
            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        $accountsPass = $this->getAccountsPassData();
        
        if ( ! $accountsPass ){
            $message['text'][] = _('Error al obtener las claves de las cuentas');
            SP_Common::wrLogInfo($message);
            return FALSE;
        }
        
        foreach ( $accountsPass as $account ){
            $this->accountId = $account->account_id;
            $this->accountUserEditId = $userId;
            
            // No realizar cambios si está en modo demo
            if ( $demoEnabled ){
                $accountsOk[] = $this->accountId;
                continue;
            }
            
            $decryptedPass = $crypt->decrypt($account->account_pass, $currentMasterPass, $account->account_IV);
            $this->accountPass = $crypt->mkEncrypt($decryptedPass,$newMasterPass);
            $this->accountIV = $crypt->strInitialVector;
            
            if ( $this->accountPass === FALSE  ){
                $errorCount++;
                continue;
            }
                                
            if ( ! $this->updateAccountPass(TRUE) ){
                $errorCount++;
                $message['text'][] = _('Fallo al actualizar la clave de la cuenta')."(".$this->accountId.")";
            }
            $accountsOk[] = $this->accountId;
        }
        
        // Vaciar el array de mensaje de log
        if ( count($message['text']) > 0 ){
            SP_Common::wrLogInfo($message);
            $message['text'] = array();
        }
        
        if ( $accountsOk ) {
            $message['text'][] = _('Cuentas actualizadas:').": ".implode(',',$accountsOk);
            SP_Common::wrLogInfo($message);
            $message['text'] = array();
        }
        
        $message['text'][] = _('Fin');
        SP_Common::wrLogInfo($message);

        if ( $errorCount > 0 ){
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Actualiza las claves de todas las cuentas en el histórico con la nueva clave maestra
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass con la nueva clave maestra
     * @param string $newHash con el nuevo hash de la clave maestra 
     * @return bool
     */
    public function updateAllAccountsHistoryMPass($currentMasterPass, $newMasterPass, $newHash){
        $idOk = array();
        $errorCount = 0;
        $demoEnabled = SP_Config::getValue('demoenabled',0);

        $message['action'] = _('Actualizar Clave Maestra (H)');
        $message['text'][] = _('Inicio');
        
        SP_Common::wrLogInfo($message);
        
        // Limpiar 'text' para los próximos mensajes
        $message['text'] = array();
       
        $crypt = new SP_Crypt();
        
        if ( !SP_Crypt::checkCryptModule() ) {
            $message['text'][] = _('Error en el módulo de encriptación');
            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        $accountsPass = $this->getAccountsHistoryPassData();
        
        if ( ! $accountsPass ){
            $message['text'][] = _('Error al obtener las claves de las cuentas');
            SP_Common::wrLogInfo($message);
            return FALSE;
        }
        
        foreach ( $accountsPass as $account ){
            // No realizar cambios si está en modo demo
            if ( $demoEnabled ){
                $idOk[] = $account->acchistory_id;
                continue;
            }

            if ( ! $this->checkAccountMPass($account->acchistory_id) ){
                $errorCount++;
                $message['text'][] = _('La clave maestra del registro no coincide')." (".$account->acchistory_id.")";
                continue;
            }
            
            $decryptedPass = $crypt->decrypt($account->acchistory_pass, $currentMasterPass, $account->acchistory_IV);
            
            $this->accountPass = $crypt->mkEncrypt($decryptedPass,$newMasterPass);
            $this->accountIV = $crypt->strInitialVector;
            
            if ( $this->accountPass === FALSE ){
                $errorCount++;
                continue;
            }
            
            if ( ! $this->updateAccountHistoryPass($account->acchistory_id, $newHash) ){
                $errorCount++;
                $message['text'][] = _('Fallo al actualizar la clave del histórico')." (".$account->acchistory_id.")";
            }
            
            $idOk[] = $account->acchistory_id;
        }

        // Vaciar el array de mensaje de log
        if ( count($message['text']) > 0 ){
            SP_Common::wrLogInfo($message);
            $message['text'] = array();
        }
        
        if ( $idOk ) {
            $message['text'][] = _('Registros actualizados:').": ".implode(',',$idOk);
            SP_Common::wrLogInfo($message);
            $message['text'] = array();
        }
        
        $message['text'][] = _('Fin');
        SP_Common::wrLogInfo($message);

        if ( $errorCount > 0 ){
            return FALSE;
        }

        return TRUE;
    }
    
    /**
     * @brief Comprueba el hash de la clave maestra del registro de histórico de una cuenta
     * @param int $id opcional, con el Id del registro a comprobar
     * @return bool
     */
    public function checkAccountMPass($id = NULL){
        if ( is_null($id) ){
            $id = $this->accountId;
        }
        
        $query = "SELECT acchistory_mPassHash FROM accHistory "
                . "WHERE acchistory_id = ".(int)$id;
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }

        if ( $queryRes[0]->acchistory_mPassHash != SP_Config::getConfigValue('masterPwd') ){
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Obtener los datos de una cuenta con el id
     * @param array $params con los campos de la BBDD a obtener
     * @return bool
     * 
     * Se guardan los datos en la variable $cacheParams de la clase para consultarlos
     * posteriormente.
     */
    private function getAccountInfoById($params) {
        if (!is_array($params)) {
            return FALSE;
        }

        if (is_array($this->cacheParams)) {
            $cache = TRUE;

            foreach ($params as $param) {
                if (!array_key_exists($param, $this->cacheParams)) {
                    $cache = FALSE;
                }
            }

            if ($cache) {
                return TRUE;
            }
        }

        $query = "SELECT " . implode(',', $params) . "
                        FROM accounts
                        LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id
                        LEFT JOIN usrData u1 ON account_userId = u1.user_id
                        LEFT JOIN usrData u2 ON account_userEditId = u2.user_id
                        LEFT JOIN customers ON account_customerId = customer_id
                        WHERE account_id = " . (int) $this->accountId . " LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        foreach ($queryRes[0] as $param => $value) {
            $this->cacheParams[$param] = $value;
        }

        return TRUE;
    }

    /**
     * @brief Calcular el hash de los datos de una cuenta
     * @return string con el hash
     * 
     * Esta función se utiliza para verificar si los datos de un formulario han sido cambiados
     * con respecto a los guardados
     */
    public function calcChangesHash(){
        $groups = '';
        
        if ( is_array($this->accountUserGroupsId)){
            $groups = implode($this->accountUserGroupsId);
        } elseif (is_array($this->accountCacheUserGroupsId) ){
            foreach ($this->accountCacheUserGroupsId as $id => $group){
                if (is_array($group) ){
                    $groups = implode($group);
                }
            } 
        }
        
        return md5($this->accountName.
        $this->accountCategoryId.
        $this->accountCustomerId.
        $groups.
        $this->accountLogin.
        $this->accountUrl.
        $this->accountNotes);
    }
}
