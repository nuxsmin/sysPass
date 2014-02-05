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
 * Extender la clase Exception para mostrar ayuda en los mensajes
 */
class MigrateException extends Exception {

    private $type;
    private $hint;

    public function __construct($type, $message, $hint, $code = 0, Exception $previous = null) {
        $this->type = $type;
        $this->hint = $hint;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    public function getHint() {
        return $this->hint;
    }

    public function getType() {
        return $this->type;
    }

}

/**
 * Esta clase es la encargada de realizar la migración de datos desde phpPMS.
 */
class SP_Migrate {
    private static $dbuser;
    private static $dbname;
    private static $dbhost;
    private static $dbc; // Database connection
    private static $customersByName;
    private static $currentQuery;
    private static $result = array();
    private static $oldConfig = array();

    /**
     * @brief Iniciar migración
     * @param array $options datos de conexión
     * @return array resultado del proceso
     *
     * Iniciar el proceso de migración desde phpPMS
     */
    public static function migrate($options) {

        if (!is_array($options)) {
            $result['error'][]['description'] = _('Faltan parámetros');
            return $result;
        }

        self::$dbname = $dbname = $options['dbname'];
        self::$dbhost = $dbhost = $options['dbhost'];

        $dbadmin = $options['dbuser'];
        $dbpass = $options['dbpass'];

        try {
            self::checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbname);
            self::checkDatabaseExist();
            self::checkSourceVersion();
            self::cleanCurrentDB();
            self::migrateCustomers();
            self::migrateAccounts();
            self::migrateAccountsGroups();
            self::migrateAccountsHistory();
            self::migrateAcountsFiles();
            self::migrateAccountsCategories();
            self::migrateUsers();
            self::migrateUsersGroups();
            self::migrateConfig();
        } catch (MigrateException $e) {
            self::$result['error'][] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
            return(self::$result);
        }
        
        self::$result['ok'][] = _('Importación finalizada');
        self::$result['ok'][] = _('Revise el registro de eventos para más detalles');
        
        return(self::$result);
    }

    /**
     * @brief Comprobar si la conexión con la BBDD.
     * @param string $dbhost host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass clave de conexión
     * @param string $dbname nombre de la base de datos
     * @return none
     *
     * Comprobar si la conexión con la base de datos de phpPMS es posible con
     * los datos facilitados.
     */
    private static function checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbname) {
        self::$dbc = new mysqli($dbhost, $dbadmin, $dbpass, $dbname);

        if (self::$dbc->connect_errno) {
            throw new MigrateException('critical',
            _('El usuario/clave de MySQL no es correcto'),
            _('Verifique el usuario de conexión para la Base de Datos'));
        }
    }

    /**
     * @brief Comprobar si la BBDD existe
     * @param string $dbhost host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass clave de conexión
     * @param string $dbname nombre de la base de datos
     * @return none
     *
     * Comprobar si la conexión con la base de datos de phpPMS es posible con
     * los datos facilitados.
     */
    private static function checkDatabaseExist() {
        $query = "SELECT COUNT(*) "
                . "FROM information_schema.tables "
                . "WHERE table_schema='" . self::$dbname . "' "
                . "AND table_name = 'users';";

        $queryRes = self::$dbc->query($query);

        if ($queryRes) {
            $row = $queryRes->fetch_row();
        }

        if (!$queryRes || $row[0] == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Limpiar los datos de sysPass
     * @return none
     *
     * Limpiar las tablas de la base de sysPass para la importación.
     */
    private static function cleanCurrentDB() {
        $tables = array('accounts', 'accHistory', 'accFiles', 'accGroups', 'categories', 'customers', 'usrGroups');

        // Limpiar datos de las tablas
        foreach ($tables as $table) {
            $query = 'TRUNCATE TABLE ' . $table;
            $queryRes = DB::doQuery($query, __FUNCTION__);

            if ( $queryRes === FALSE) {
                throw new MigrateException('critical',
                _('Error al vaciar tabla') . ' (' . $table . ')',
                DB::$txtError);
            }
        }

        $currentUserId = $_SESSION['uid'];

        // Limpiar datos de usuarios manteniendo el usuario actual
        if (self::checkAdminAccount($currentUserId)) {
            $query = 'DELETE FROM usrData WHERE user_id != ' . $currentUserId;
            $queryRes = DB::doQuery($query, __FUNCTION__);

            if ( $queryRes === FALSE ) {
                throw new MigrateException('critical',
                _('Error al vaciar tabla') . ' (' . $table . ')',
                DB::$txtError);
            }
        } else {
            throw new MigrateException('critical',
            _('Usuario actual no es administrador de la aplicación'),
            DB::$txtError);
        }
    }

    /**
     * @brief Comprobar si el usuario actual es administrador de la aplicación
     * @return bool
     */
    private static function checkAdminAccount($currentUserId) {
        $query = 'SELECT COUNT(*) '
                . 'FROM usrData '
                . 'WHERE user_id = ' . $currentUserId . ' AND user_isAdminApp = 1';
        $queryRes = DB::doQuery($query,__FUNCTION__);

        if ($queryRes !== 1) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Obtener los clientes desde phpPMS
     * @return array con los clientes
     */
    private static function getCustomers() {
        $query = 'SELECT DISTINCT vacCliente FROM accounts';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener los clientes'),
            self::$dbc->error);
        }

        while ($row = @$queryRes->fetch_row()) {
            $customers[] = trim($row[0]);
        }

        return $customers;
    }

    /**
     * @brief Migrar los clientes desde phpPMS
     * @return array resultado
     */
    private static function migrateCustomers() {
        $customers = self::getCustomers();

        $totalRecords = count($customers);
        $num = 0;

        foreach ($customers as $customer) {
            SP_Customer::$customerName = $customer;

            if (!SP_Customer::checkDupCustomer()) {
                $num++;
                continue;
            }

            if (!SP_Customer::addCustomer()) {
                throw new MigrateException('critical',
                _('No es posible crear el cliente'),
                _('Contacte con el desarrollador'));
            }
        }

        $message['action'] = _('Importar Clientes');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Clientes')." - $num / $totalRecords";
    }

    /**
     * @brief Migrar las cuentas desde phpPMS
     * @return array resultado
     */
    private static function migrateAccounts() {
        $query = 'SELECT intAccountId,'
                . 'intUGroupFId,'
                . 'intUserFId,'
                . 'intUEditFId,'
                . 'vacCliente,vacName,'
                . 'intCategoryFid,'
                . 'vacLogin,'
                . 'vacUrl,'
                . 'vacAccountGroups,'
                . 'vacPassword,'
                . 'vacMd5Password,'
                . 'vacInitialValue,'
                . 'txtNotice,'
                . 'intCountView,'
                . 'intCountDecrypt,'
                . 'datAdded,datChanged '
                . 'FROM accounts ';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener cuentas'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertAccounts($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Cuentas');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Cuentas')." - $num / $totalRecords";
    }

    /**
     * @brief Insertar una cuenta en sysPass
     * @param array $account con los datos de la cuenta
     * @return bool
     */    
    private static function insertAccounts($account) {
        if (!is_array(self::$customersByName)) {
            $customers = SP_Customer::getCustomers(NULL,TRUE);
            self::$customersByName = array_flip($customers);
        }

        $customer = trim($account['vacCliente']);

        if (array_key_exists($customer, self::$customersByName)) {
            $customerId = self::$customersByName[$customer];
        } else {
            self::$result['error'][] = _('Cliente no encontrado').": " . $account['vacCliente'];

            return FALSE;
        }

        $query = "INSERT INTO accounts SET
                    account_id = " . $account['intAccountId'] . ",
                    account_userGroupId = " . $account['intUGroupFId'] . ",
                    account_userId = " . $account['intUserFId'] . ",
                    account_userEditId = " . $account['intUEditFId'] . ",
                    account_customerId = " . $customerId . ",
                    account_name = '" . DB::escape($account['vacName']) . "',
                    account_categoryId = " . $account['intCategoryFid'] . ",
                    account_login = '" . DB::escape($account['vacLogin']) . "',
                    account_url = '" . DB::escape($account['vacUrl']) . "',
                    account_pass = '" . $account['vacPassword'] . "',
                    account_IV = '" . $account['vacInitialValue'] . "',
                    account_notes = '" . DB::escape($account['txtNotice']) . "',
                    account_countView = " . $account['intCountView'] . ",
                    account_countDecrypt = " . $account['intCountDecrypt'] . ",
                    account_dateAdd = '" . $account['datAdded'] . "',
                    account_dateEdit = '" . $account['datChanged'] . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            self::$currentQuery = DB::escape($query);
            throw new MigrateException('critical',
            _('Error al migrar cuenta'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar las grupos secundarios de las cuentas desde phpPMS
     * @return array resultado
     */
    private static function migrateAccountsGroups() {
        $query = 'SELECT intAccId,'
                . 'intUGroupId '
                . 'FROM acc_usergroups';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener los grupos de cuentas'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertAccountsGroups($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Grupos de Cuentas');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
//        self::$result['ok'][] = _('Importar Grupos de Cuentas')." - $num / $totalRecords";
    }

    /**
     * @brief Insertar los grupos secundarios de una cuenta en sysPass
     * @param array $accountGroup con los datos de los grupos secundarios
     * @return bool
     */   
    private static function insertAccountsGroups($accountGroup) {
        $query = "INSERT INTO accGroups "
                . "SET accgroup_accountId = " . $accountGroup['intAccId'] . ","
                . "accgroup_groupId = " . $accountGroup['intUGroupId'];

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear grupos de cuentas'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar el historail de las cuentas desde phpPMS
     * @return array resultado
     */
    private static function migrateAccountsHistory() {
        $query = 'SELECT intAccountId,'
                . 'intUGroupFId,'
                . 'intUserFId,'
                . 'intUEditFId,'
                . 'vacCliente,'
                . 'vacName,'
                . 'intCategoryFid,'
                . 'vacLogin,'
                . 'vacUrl,'
                . 'vacPassword,'
                . 'vacInitialValue,'
                . 'txtNotice,'
                . 'intCountView,'
                . 'intCountDecrypt,'
                . 'datAdded,'
                . 'datChanged,'
                . 'blnModificada,'
                . 'blnEliminada '
                . 'FROM acc_history';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener el historico de cuentas'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertAccountsHistory($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Histórico de Cuentas');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Histórico de Cuentas')." - $num / $totalRecords";
    }

    /**
     * @brief Insertar el historial de una cuenta en sysPass
     * @param array $accountHistory con los datos del historial de la cuenta
     * @return bool
     */ 
    private static function insertAccountsHistory($accountHistory) {
        if (!is_array(self::$customersByName)) {
            $customers = SP_Customer::getCustomers(NULL,TRUE);
            self::$customersByName = array_flip($customers);
        }

        $customer = trim($accountHistory['vacCliente']);

        if (array_key_exists($customer, self::$customersByName)) {
            $customerId = self::$customersByName[$customer];
        } else {
            return FALSE;
        }

        $query = "INSERT INTO accHistory SET
                    acchistory_accountId = " . $accountHistory['intAccountId'] . ",
                    acchistory_userGroupId = " . $accountHistory['intUGroupFId'] . ",
                    acchistory_userId = " . $accountHistory['intUserFId'] . ",
                    acchistory_userEditId = " . $accountHistory['intUEditFId'] . ",
                    acchistory_customerId = " . $customerId . ",
                    acchistory_name = '" . DB::escape($accountHistory['vacName']) . "',
                    acchistory_categoryId = " . $accountHistory['intCategoryFid'] . ",
                    acchistory_login = '" . DB::escape($accountHistory['vacLogin']) . "',
                    acchistory_url = '" . DB::escape($accountHistory['vacUrl']) . "',
                    acchistory_pass = '" . $accountHistory['vacPassword'] . "',
                    acchistory_IV = '" . $accountHistory['vacInitialValue'] . "',
                    acchistory_notes = '" . DB::escape($accountHistory['txtNotice']) . "',
                    acchistory_countView = " . $accountHistory['intCountView'] . ",
                    acchistory_countDecrypt = " . $accountHistory['intCountDecrypt'] . ",
                    acchistory_dateAdd = '" . $accountHistory['datAdded'] . "',
                    acchistory_dateEdit = '" . $accountHistory['datChanged'] . "',
                    acchistory_isModify = " . $accountHistory['blnModificada'] . ",
                    acchistory_isDeleted = " . $accountHistory['blnEliminada'];

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear historico de cuentas'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar los archivos de de las cuentas desde phpPMS
     * @return array resultado
     */
    private static function migrateAcountsFiles() {
        $query = 'SELECT intAccountId,'
                . 'vacName,'
                . 'vacType,'
                . 'intSize,'
                . 'blobContent,'
                . 'vacExtension '
                . 'FROM files';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener los archivos de cuentas'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertAccountsFiles($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Archivos de Cuentas');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Archivos de Cuentas')." - $num / $totalRecords";
    }

   /**
     * @brief Insertar los archivos de una cuenta en sysPass
     * @param array $accountFile con los datos del archivo
     * @return bool
     */ 
    private static function insertAccountsFiles($accountFile) {
        $query = "INSERT INTO accFiles "
                . "SET accfile_accountId = " . $accountFile['intAccountId'] . ","
                . "accfile_name = '" . DB::escape($accountFile['vacName']) . "',"
                . "accfile_type = '" . DB::escape($accountFile['vacType']) . "',"
                . "accfile_size = " . $accountFile['intSize'] . ","
                . "accfile_content = '" . DB::escape($accountFile['blobContent']) . "',"
                . "accfile_extension = '" . DB::escape($accountFile['vacExtension']) . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear archivos de cuentas'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar las categorías de las cuentas desde phpPMS
     * @return array resultado
     */
    private static function migrateAccountsCategories() {
        $query = 'SELECT intCategoryId,'
                . 'vacCategoryName '
                . 'FROM categories';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener las categorías de cuentas'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertAccountsCategories($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Categorías de Cuentas');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Categorías de Cuentas')." - $num / $totalRecords";
    }

   /**
     * @brief Insertar las categorías en sysPass
     * @param array $accountCategory con los datos de la categoría
     * @return bool
     */ 
    private static function insertAccountsCategories($accountCategory) {
        $query = "INSERT INTO categories "
                . "SET category_id = " . $accountCategory['intCategoryId'] . ","
                . "category_name = '" . DB::escape($accountCategory['vacCategoryName']) . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear categorías de cuentas'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar los usuarios desde desde phpPMS
     * @return array resultado
     */
    private static function migrateUsers() {
        $query = 'SELECT intUserId,'
                . 'vacUName,'
                . 'intUGroupFid,'
                . 'vacULogin,'
                . 'vacUPassword,'
                . 'vacUEmail,'
                . 'txtUNotes,'
                . 'intUCount,'
                . 'intUProfile,'
                . 'datULastLogin,'
                . 'blnIsAdminApp,'
                . 'blnIsAdminAcc,'
                . 'vacUserMPwd,'
                . 'vacUserMIv,'
                . 'datULastUpdate,'
                . 'datUserLastUpdateMPass,'
                . 'blnFromLdap,'
                . 'blnDisabled '
                . 'FROM users '
                . 'WHERE intUserId <> ' . $_SESSION['uid'];
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener los usuarios'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertUsers($row)) {
                $num++;
            }
        }

        $message['action'] = _('Importar Usuarios');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Usuarios')." - $num / $totalRecords";
    }

   /**
    * @brief Insertar los usuarios en sysPass
    * @param array $users con los datos del usuario
    * @return bool
    * 
    * El usuario importado está deshabilitado
    */ 
    private static function insertUsers($users) {
        $query = "INSERT INTO usrData "
                . "SET user_id = " . $users['intUserId'] . ","
                . "user_name = '" . DB::escape($users['vacUName']) . "',"
                . "user_groupId = " . $users['intUGroupFid'] . ","
                . "user_login = '" . DB::escape($users['vacULogin']) . "',"
                . "user_pass = '" . DB::escape($users['vacUPassword']) . "',"
                . "user_mPass = '" . DB::escape($users['vacUserMPwd']) . "',"
                . "user_mIV = '" . DB::escape($users['vacUserMIv']) . "',"
                . "user_email = '" . DB::escape($users['vacUEmail']) . "',"
                . "user_notes = '" . DB::escape($users['txtUNotes']) . "',"
                . "user_count = " . $users['intUCount'] . ","
                . "user_profileId = 0,"
                . "user_lastLogin = '" . $users['datULastLogin'] . "',"
                . "user_lastUpdate = '" . $users['datULastUpdate'] . "',"
                . "user_lastUpdateMPass = " . $users['datUserLastUpdateMPass'] . ","
                . "user_isAdminApp = " . $users['blnIsAdminApp'] . ","
                . "user_isAdminAcc = " . $users['blnIsAdminAcc'] . ","
                . "user_isLdap = " . $users['blnFromLdap'] . ","
                . "user_isDisabled = 1,"
                . "user_isMigrate = 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear usuarios'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Migrar los grupos de usuarios desde desde phpPMS
     * @return array resultado
     */
    private static function migrateUsersGroups() {
        $query = 'SELECT intUGroupId,'
                . 'vacUGroupName,'
                . 'vacUGroupDesc '
                . 'FROM usergroups';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener los grupos de usuarios'),
            self::$dbc->error);
        }

        $totalRecords = $queryRes->num_rows;
        $num = 0;

        while ($row = @$queryRes->fetch_assoc()) {
            if (self::insertUsersGroups($row)) {
                $num++;
            }
        }
        
        $message['action'] = _('Importar Grupos de Usuarios');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalRecords;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Grupos de Usuarios')." - $num / $totalRecords";
    }

   /**
    * @brief Insertar los grupos de usuarios en sysPass
    * @param array $usersGroups con los datos del grupo
    * @return bool
    */ 
    private static function insertUsersGroups($usersGroups) {
        $query = "INSERT INTO usrGroups "
                . "SET usergroup_id = " . $usersGroups['intUGroupId'] . ","
                . "usergroup_name = '" . DB::escape($usersGroups['vacUGroupName']) . "',"
                . "usergroup_description = '" . DB::escape($usersGroups['vacUGroupDesc']) . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            throw new MigrateException('critical',
            _('Error al crear los grupos de usuarios'),
            DB::$txtError);
        }

        return TRUE;
    }

    /**
     * @brief Obtener la configuración desde desde phpPMS
     * @return none
     */
    private static function getSourceConfig(){
        $query = 'SELECT vacValue as value,'
                . 'vacParameter as parameter '
                . 'FROM config';
        $queryRes = self::$dbc->query($query);

        if (!$queryRes) {
            throw new MigrateException('critical',
            _('Error al obtener la configuración'),
            self::$dbc->error);
        }

        while ($row = @$queryRes->fetch_assoc()) {
            self::parseSourceConfig($row);
        }
    }

   /**
    * @brief Parsear los valores de configuración y adaptarlos
    * @param array $config con los datos de configuración
    * @return none
    */ 
    private static function parseSourceConfig($config){
        if ( !is_array($config) ){
            return FALSE;
        }
        
        if ( strtolower($config['value']) == 'true' || strtolower($config['value']) == 'on' ){
            $value = 1;
        } else{
            $value = (is_numeric($config['value'])) ? (int)$config['value'] : trim($config['value']);
        }
        
        // Guardar la configuración anterior
        self::$oldConfig[$config['parameter']] = $value;
		
        //error_log($config['parameter'].' >> '.$value);
    }

   /**
     * @brief Migrar la configuración desde phpPMS
     * @return array resultado
     */
    private static function migrateConfig(){
    	// Obtener la configuración actual
        self::getSourceConfig();
		        
        $skip = array ('version',
			'installed',
			'install',
			'dbhost',
			'dbname',
			'dbuser',
			'dbpass',
			'siteroot',
			'sitelang',
			'sitename',
			'siteshortname',
			'md5_pass',
			'password_show',
			'lastupdatempass',
			'passwordsalt');
        //$savedConfig = array_diff_key($skip, SP_Config::getKeys());
        
	$totalParams = count(self::$oldConfig);
	$num = 0;
		
        // Guardar la nueva configuración
        foreach ( self::$oldConfig as $key => $value){
            if ( array_key_exists($key, $skip) ){
                continue;
            }
            SP_Config::setValue($key, $value);
            $num++;
        }
        
        $message['action'] = _('Importar Configuración');
        $message['text'][] = 'OK';
        $message['text'][] = _('Registros').': ' . $num . ' / ' . $totalParams;

        SP_Common::wrLogInfo($message);
        
//        self::$result['ok'][] = _('Importar Configuración')." - $num / $totalParams";
    }

   /**
     * @brief Comprobar la versión de phpPMS
     * @return none
     */
    private static function checkSourceVersion(){
        if ( ! isset( self::$oldConfig['version']) ){
            self::getSourceConfig();
        }
        
        if ( self::$oldConfig['version'] != "0.973b" ){
            throw new MigrateException('critical',
            _('La versión no es compatible').'('.self::$oldConfig['version'].')',
            _('Actualice a la última versión de phpPMS'));
        }
    }
}