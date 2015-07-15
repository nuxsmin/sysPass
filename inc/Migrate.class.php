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
 * Esta clase es la encargada de realizar la migración de datos desde phpPMS.
 */
class Migrate
{
//    private static $dbuser;
    private static $_dbc; // Database connection
    private static $_customersByName;
    private static $_currentQuery;
    private static $_result = array();
    private static $_oldConfig = array();

    /**
     * Iniciar migración desde phpPMS.
     *
     * @param array $options datos de conexión
     * @return array resultado del proceso
     */
    public static function migrate($options)
    {

        if (!is_array($options)) {
            $result['error'][]['description'] = _('Faltan parámetros');
            return $result;
        }

        $dbname = $options['dbname'];

        if (preg_match('/(.*):(\d{1,5})/', $options['dbhost'], $match)){
            $dbhost = $match[1];
            $dbport = $match[2];
        } else {
            $dbhost = $options['dbhost'];
            $dbport = 3306;
        }

        $dbadmin = $options['dbuser'];
        $dbpass = $options['dbpass'];

        try {
            self::checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbname, $dbport);
            self::checkDatabaseExist($dbname);
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
        } catch (SPException $e) {
            self::$_result['error'][] = array(
                'type' => $e->getType(),
                'description' => $e->getMessage(),
                'hint' => $e->getHint()
            );
            return (self::$_result);
        }

        self::$_result['ok'][] = _('Importación finalizada');
        self::$_result['ok'][] = _('Revise el registro de eventos para más detalles');

        return (self::$_result);
    }

    /**
     * Comprobar si la conexión con la BBDD de phpPMS es posible.
     *
     * @param string $dbhost  host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass  clave de conexión
     * @param string $dbname  nombre de la base de datos
     * @param string $dbport  puerto de conexión
     * @throws SPException
     */
    private static function checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbname, $dbport)
    {
        try {
            $dsn = 'mysql:host=' . $dbhost . ';dbname=' . $dbname . ';dbport=' . $dbport . ';charset=utf8';
            self::$_dbc = new \PDO($dsn, $dbadmin, $dbpass);
        } catch (\PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL
                , _('No es posible conectar con la BD')
                , _('Compruebe los datos de conexión') . '<br>' . $e->getMessage());
        }
    }

    /**
     * Comprobar si la BBDD existe.
     *
     * @param string $dbname  nombre de la base de datos
     * @return bool
     */
    private static function checkDatabaseExist($dbname)
    {
        $query = 'SELECT COUNT(*) '
            . 'FROM information_schema.tables '
            . 'WHERE table_schema = \'' . $dbname . '\' '
            . 'AND table_name = \'usrData\' LIMIT 1';

        return (intval(self::$_dbc->query($query)->fetchColumn()) === 0);
    }

    /**
     * Comprobar la versión de phpPMS.
     *
     * @throws SPException
     */
    private static function checkSourceVersion()
    {
        if (!isset(self::$_oldConfig['version'])) {
            self::getSourceConfig();
        }

        if (self::$_oldConfig['version'] != "0.973b") {
            throw new SPException(SPException::SP_CRITICAL,
                _('La versión no es compatible') . '(' . self::$_oldConfig['version'] . ')',
                _('Actualice a la última versión de phpPMS'));
        }
    }

    /**
     * Obtener la configuración desde desde phpPMS.
     *
     * @throws SPException
     */
    private static function getSourceConfig()
    {
        $query = 'SELECT vacValue as value,vacParameter as parameter FROM config';

        try {
            self::parseSourceConfig(self::$_dbc->query($query));
        } catch (\PDOException $e) {

            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener la configuración'),
                $e->getMessage());
        }
    }

    /**
     * Parsear los valores de configuración de phpPMS y adaptarlos a sysPass.
     *
     * @param array $config con los datos de configuración
     * @return bool
     */
    private static function parseSourceConfig($config)
    {
        if (!is_array($config)) {
            return false;
        }

        if (strtolower($config['value']) == 'true' || strtolower($config['value']) == 'on') {
            $value = 1;
        } else {
            $value = (is_numeric($config['value'])) ? (int)$config['value'] : trim($config['value']);
        }

        // Guardar la configuración anterior
        self::$_oldConfig[$config['parameter']] = $value;
    }

    /**
     * Limpiar los datos de sysPass.
     * Limpiar las tablas de la base de sysPass para la importación.
     *
     * @throws SPException
     */
    private static function cleanCurrentDB()
    {
        $tables = array('accounts', 'accHistory', 'accFiles', 'accGroups', 'categories', 'customers', 'usrGroups');

        // Limpiar datos de las tablas
        foreach ($tables as $table) {
            $query = 'TRUNCATE TABLE ' . $table;

            if (DB::getQuery($query, __FUNCTION__) === false) {
                throw new SPException(SPException::SP_CRITICAL,
                    _('Error al vaciar tabla') . ' (' . $table . ')',
                    DB::$txtError);
            }
        }

        $currentUserId = Session::getUserId();

        // Limpiar datos de usuarios manteniendo el usuario actual
        if (self::checkAdminAccount($currentUserId)) {
            $query = 'DELETE FROM usrData WHERE user_id != ' . $currentUserId;

            if (DB::getQuery($query, __FUNCTION__) === false) {
                throw new SPException(SPException::SP_CRITICAL,
                    _('Error al vaciar tabla') . ' (' . $table . ')',
                    DB::$txtError);
            }
        } else {
            throw new SPException(SPException::SP_CRITICAL,
                _('Usuario actual no es administrador de la aplicación'), 1);
        }
    }

    /**
     * Comprobar si el usuario actual es administrador de la aplicación.
     *
     * @param int $currentUserId con el Id del usuario de la sesión actual
     * @return bool
     */
    private static function checkAdminAccount($currentUserId)
    {
        $query = 'SELECT user_id FROM usrData WHERE user_id = :id AND user_isAdminApp = 1 LIMIT 1';

        $data['id'] = $currentUserId;

        DB::getQuery($query, __FUNCTION__, $data);

        return (DB::$lastNumRows === 0);
    }

    /**
     * Migrar los clientes desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateCustomers()
    {
        $customers = self::getCustomers();

        $totalRecords = count($customers);
        $num = 0;

        foreach ($customers as $customer) {
            Customer::$customerName = $customer;

            try {
                Customer::addCustomer();
                $num++;
            } catch (SPException $e) {
                if ($e->getType() === SPException::SP_WARNING){
                    continue;
                }
                throw new SPException(SPException::SP_CRITICAL,
                    _('No es posible crear el cliente'),
                    _('Contacte con el desarrollador'));
            }
        }

        $log = new Log(_('Importar Clientes'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Obtener los clientes desde phpPMS.
     *
     * @throws SPException
     * @return array con los clientes
     */
    private static function getCustomers()
    {
        $query = 'SELECT DISTINCT vacCliente FROM accounts';

        try {
            foreach (self::$_dbc->query($query) as $row) {
                $customers[] = trim($row['vacCliente']);
            }

            return $customers;
        } catch (\PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener los clientes'),
                $e->getMessage());
        }
    }

    /**
     * Migrar las cuentas desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateAccounts()
    {
        $query = 'SELECT intAccountId,'
            . 'intUGroupFId,'
            . 'intUserFId,'
            . 'intUEditFId,'
            . 'vacCliente,vacName,'
            . 'intCategoryFid,'
            . 'vacLogin,'
            . 'vacUrl,'
            . 'vacPassword,'
            . 'vacMd5Password,'
            . 'vacInitialValue,'
            . 'txtNotice,'
            . 'intCountView,'
            . 'intCountDecrypt,'
            . 'datAdded,datChanged '
            . 'FROM accounts ';

        $totalRecords = 0;
        $num = 0;

        try {
            foreach (self::$_dbc->query($query) as $row) {
                if (self::insertAccounts($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch (\PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener cuentas'),
                $e->getMessage());
        }

        $log = new Log(_('Importar Cuentas'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar una cuenta en sysPass.
     *
     * @param array $account con los datos de la cuenta
     * @throws SPException
     * @return bool
     */
    private static function insertAccounts($account)
    {
        if (!is_array(self::$_customersByName)) {
            $customers = Customer::getCustomers(NULL, true);
            self::$_customersByName = array_flip($customers);
        }

        $customer = trim($account['vacCliente']);

        if (array_key_exists($customer, self::$_customersByName)) {
            $customerId = self::$_customersByName[$customer];
        } else {
            self::$_result['error'][] = _('Cliente no encontrado') . ": " . $account['vacCliente'];

            return false;
        }

        $query = 'INSERT INTO accounts SET ' .
            'account_id = :id,' .
            'account_userGroupId = :userGroupId,' .
            'account_userId = :userId,' .
            'account_userEditId = :userEditId,' .
            'account_customerId = :customerId,' .
            'account_name = :name,' .
            'account_categoryId = :categoryId,' .
            'account_login = :login,' .
            'account_url = :url,' .
            'account_pass = :pass,' .
            'account_IV = :iv,' .
            'account_notes = :notes,' .
            'account_countView = :countView,' .
            'account_countDecrypt = :countDecrypt,' .
            'account_dateAdd = :dateAdd,' .
            'account_dateEdit = :dateEdit';

        $data['id'] = $account['intAccountId'];
        $data['userGroupId'] = $account['intUGroupFId'];
        $data['userId'] = $account['intUserFId'];
        $data['userEditId'] = $account['intUEditFId'];
        $data['customerId'] = $customerId;
        $data['name'] = $account['vacName'];
        $data['categoryId'] = $account['intCategoryFid'];
        $data['login'] = $account['vacLogin'];
        $data['url'] = $account['vacUrl'];
        $data['pass'] = $account['vacPassword'];
        $data['iv'] = $account['vacInitialValue'];
        $data['notes'] = $account['txtNotice'];
        $data['countView'] = $account['intCountView'];
        $data['countDecrypt'] = $account['intCountDecrypt'];
        $data['dateAdd'] = $account['datAdded'];
        $data['dateEdit'] = $account['datChanged'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            self::$_currentQuery = DB::escape($query);
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al migrar cuenta'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar las grupos secundarios de las cuentas desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateAccountsGroups()
    {
        $query = 'SELECT intAccId,intUGroupId FROM acc_usergroups';

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertAccountsGroups($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener los grupos de cuentas'),
                $e->getMessage());
        }

        $log = new Log(_('Importar Grupos de Cuentas'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar los grupos secundarios de una cuenta en sysPass.
     *
     * @param array $accountGroup con los datos de los grupos secundarios
     * @throws SPException
     * @return bool
     */
    private static function insertAccountsGroups($accountGroup)
    {
        $query = 'INSERT INTO accGroups SET accgroup_accountId = :accountId,accgroup_groupId = :groudId';

        $data['accountId'] = $accountGroup['intAccId'];
        $data['groupId'] = $accountGroup['intUGroupId'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear grupos de cuentas'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar el historail de las cuentas desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateAccountsHistory()
    {
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

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertAccountsHistory($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener el historico de cuentas'),
                self::$_dbc->error);
        }

        $log = new Log(_('Importar Histórico de Cuentas'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar el historial de una cuenta en sysPass.
     *
     * @param array $accountHistory con los datos del historial de la cuenta
     * @throws SPException
     * @return bool
     */
    private static function insertAccountsHistory($accountHistory)
    {
        if (!is_array(self::$_customersByName)) {
            $customers = Customer::getCustomers(null, true);
            self::$_customersByName = array_flip($customers);
        }

        $customer = trim($accountHistory['vacCliente']);

        if (array_key_exists($customer, self::$_customersByName)) {
            $customerId = self::$_customersByName[$customer];
        } else {
            return false;
        }

        $query = 'INSERT INTO accHistory SET ' .
            'acchistory_accountId = :id,' .
            'acchistory_userGroupId = :userGroupId,' .
            'acchistory_userId = :userId,' .
            'acchistory_userEditId = :userEditId,' .
            'acchistory_customerId = :customerId,' .
            'acchistory_name = :name,' .
            'acchistory_categoryId = :categoryId,' .
            'acchistory_login = :login,' .
            'acchistory_url = :url,' .
            'acchistory_pass = :pass,' .
            'acchistory_IV = :iv,' .
            'acchistory_notes = :notes,' .
            'acchistory_countView = :countView,' .
            'acchistory_countDecrypt = :countDecrypt,' .
            'acchistory_dateAdd = :dateAdd,' .
            'acchistory_dateEdit = :dateEdit,' .
            'acchistory_isModify = :isModify,' .
            'acchistory_isDeleted = :isDeleted';

        $data['id'] = $accountHistory['intAccountId'];
        $data['userGroupId'] = $accountHistory['intUGroupFId'];
        $data['userId'] = $accountHistory['intUserFId'];
        $data['userEditId'] = $accountHistory['intUEditFId'];
        $data['customerId'] = $customerId;
        $data['name'] = $accountHistory['vacName'];
        $data['categoryId'] = $accountHistory['intCategoryFid'];
        $data['login'] = $accountHistory['vacLogin'];
        $data['url'] = $accountHistory['vacUrl'];
        $data['pass'] = $accountHistory['vacPassword'];
        $data['iv'] = $accountHistory['vacInitialValue'];
        $data['notes'] = $accountHistory['txtNotice'];
        $data['countView'] = $accountHistory['intCountView'];
        $data['countDecrypt'] = $accountHistory['intCountDecrypt'];
        $data['dateAdd'] = $accountHistory['datAdded'];
        $data['dateEdit'] = $accountHistory['datChanged'];
        $data['isModify'] = $accountHistory['blnModificada'];
        $data['isDeleted'] = $accountHistory['blnEliminada'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear historico de cuentas'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar los archivos de de las cuentas desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateAcountsFiles()
    {
        $query = 'SELECT intAccountId,'
            . 'vacName,'
            . 'vacType,'
            . 'intSize,'
            . 'blobContent,'
            . 'vacExtension '
            . 'FROM files';

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertAccountsFiles($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener los archivos de cuentas'),
                self::$_dbc->error);
        }

        $log = new Log(_('Importar Archivos de Cuentas'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar los archivos de una cuenta en sysPass.
     *
     * @param array $accountFile con los datos del archivo
     * @throws SPException
     * @return bool
     */
    private static function insertAccountsFiles($accountFile)
    {
        $query = 'INSERT INTO accFiles '
            . 'SET accfile_accountId = :id,'
            . 'accfile_name = :name,'
            . 'accfile_type = :type,'
            . 'accfile_size = :size,'
            . 'accfile_content = :blobcontent,'
            . 'accfile_extension = :extension';

        $data['id'] = $accountFile['intAccountId'];
        $data['name'] = $accountFile['vacName'];
        $data['type'] = $accountFile['vacType'];
        $data['size'] = $accountFile['intSize'];
        $data['blobcontent'] = $accountFile['blobContent'];
        $data['extension'] = $accountFile['vacExtension'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear archivos de cuentas'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar las categorías de las cuentas desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateAccountsCategories()
    {
        $query = 'SELECT intCategoryId,vacCategoryName FROM categories';

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertAccountsCategories($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener las categorías de cuentas'),
                self::$_dbc->error);
        }

        $log = new Log(_('Importar Categorías de Cuentas'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar las categorías en sysPass.
     *
     * @param array $accountCategory con los datos de la categoría
     * @throws SPException
     * @return bool
     */
    private static function insertAccountsCategories($accountCategory)
    {
        $query = 'INSERT INTO categories SET category_id = :id,category_name = :name';

        $data['id'] = $accountCategory['intCategoryId'];
        $data['name'] = $accountCategory['vacCategoryName'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear categorías de cuentas'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar los usuarios desde desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateUsers()
    {
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
            . 'WHERE intUserId <> ' . Session::getUserId();

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertUsers($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener los usuarios'),
                self::$_dbc->error);
        }

        $log = new Log(_('Importar Usuarios'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar los usuarios en sysPass.
     *
     * @param array $users con los datos del usuario
     * @throws SPException
     * @return bool
     *
     * El usuario importado está deshabilitado
     */
    private static function insertUsers($users)
    {
        $query = 'INSERT INTO usrData '
            . 'SET user_id = :id,'
            . 'user_name = :name,'
            . 'user_groupId = :goupId,'
            . 'user_login = :login,'
            . 'user_pass = :pass,'
            . 'user_mPass = :mpass,'
            . 'user_mIV = :miv,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_count = :count,'
            . 'user_profileId = 0,'
            . 'user_lastLogin = :lastLogin,'
            . 'user_lastUpdate = :lastUpdate,'
            . 'user_lastUpdateMPass = :lastUpdateMPass,'
            . 'user_isAdminApp = :isAdminApp,'
            . 'user_isAdminAcc = :isAdminAcc,'
            . 'user_isLdap = :isLdap,'
            . 'user_isDisabled = 1,'
            . 'user_isMigrate = 1';

        $data['id'] = $users['intUserId'];
        $data['name'] = $users['vacUName'];
        $data['groupId'] = $users['intUGroupFid'];
        $data['login'] = $users['vacULogin'];
        $data['pass'] = $users['vacUPassword'];
        $data['mpass'] = $users['vacUserMPwd'];
        $data['miv'] = $users['vacUserMIv'];
        $data['email'] = $users['vacUEmail'];
        $data['notes'] = $users['txtUNotes'];
        $data['count'] = $users['intUCount'];
        $data['lastLogin'] = $users['datULastLogin'];
        $data['lastUpdate'] = $users['datULastUpdate'];
        $data['lastUpdateMPass'] = $users['datUserLastUpdateMPass'];
        $data['isAdminApp'] = $users['blnIsAdminApp'];
        $data['isAdminAcc'] = $users['blnIsAdminAcc'];
        $data['isLdap'] = $users['blnFromLdap'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear usuarios'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar los grupos de usuarios desde desde phpPMS.
     *
     * @throws SPException
     * @return array resultado
     */
    private static function migrateUsersGroups()
    {
        $query = 'SELECT intUGroupId,vacUGroupName,vacUGroupDesc FROM usergroups';

        $totalRecords = 0;
        $num = 0;

        try {
            foreach(self::$_dbc->query($query) as $row){
                if (self::insertUsersGroups($row)) {
                    $num++;
                }
                $totalRecords++;
            }
        } catch(\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al obtener los grupos de usuarios'),
                self::$_dbc->error);
        }

        $log = new Log(_('Importar Grupos de Usuarios'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalRecords);
        $log->writeLog();
    }

    /**
     * Insertar los grupos de usuarios en sysPass.
     *
     * @param array $usersGroups con los datos del grupo
     * @throws SPException
     * @return bool
     */
    private static function insertUsersGroups($usersGroups)
    {
        $query = 'INSERT INTO usrGroups '
            . 'SET usergroup_id = :id,'
            . 'usergroup_name = :name,'
            . 'usergroup_description = :description';

        $data['id'] = $usersGroups['intUGroupId'];
        $data['name'] = $usersGroups['vacUGroupName'];
        $data['description'] = $usersGroups['vacUGroupDesc'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear los grupos de usuarios'),
                DB::$txtError);
        }

        return true;
    }

    /**
     * Migrar la configuración desde phpPMS.
     *
     * @return array resultado
     */
    private static function migrateConfig()
    {
        // Obtener la configuración actual
        self::getSourceConfig();

        $skip = array('version',
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

        $totalParams = count(self::$_oldConfig);
        $num = 0;

        // Guardar la nueva configuración
        foreach (self::$_oldConfig as $key => $value) {
            if (array_key_exists($key, $skip)) {
                continue;
            }
            Config::setValue($key, $value);
            $num++;
        }

        $log = new Log(_('Importar Configuración'));
        $log->addDescription('OK');
        $log->addDescription(_('Registros') . ': ' . $num . '/' . $totalParams);
        $log->writeLog();
    }
}