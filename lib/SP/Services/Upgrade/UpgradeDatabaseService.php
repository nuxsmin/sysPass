<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Upgrade;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Config\ConfigService;
use SP\Services\Service;
use SP\Storage\Database;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class UpgradeDatabaseService
 *
 * @package SP\Services\Upgrade
 */
class UpgradeDatabaseService extends Service
{
    /**
     * @var array Versiones actualizables
     */
    const DB_UPGRADES = ['110', '112.1', '112.2', '112.3', '112.13', '112.19', '112.20', '120.01', '120.02', '130.16011001', '130.16100601', '200.17011302', '200.17011701', '210.17022601', '213.17031402', '220.17050101'];
    const AUX_UPGRADES = ['120.01', '120.02', '200.17010901', '200.17011202'];
    const APP_UPGRADES = ['210.17022601'];
    /**
     * @var string Versión de la BBDD
     */
    private static $currentDbVersion;
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var Database
     */
    protected $db;

    /**
     * Inicia el proceso de actualización de la BBDD.
     *
     * @param int $version con la versión de la BBDD actual
     * @return bool
     * @throws UpgradeException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function doUpgrade($version)
    {
        $this->eventDispatcher->notifyEvent('upgrade.db.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualizar BBDD')))
        );

        self::$currentDbVersion = UpgradeUtil::fixVersionNumber($this->configService->getByParam('version'));

        foreach (self::DB_UPGRADES as $dbVersion) {
            if (Util::checkVersion($version, $dbVersion)) {
                if ($this->auxPreDbUpgrade($dbVersion) === false) {
                    throw new UpgradeException(
                        __u('Error al aplicar la actualización auxiliar'),
                        UpgradeException::CRITICAL,
                        __u('Compruebe el registro de eventos para más detalles')
                    );
                }

                if ($this->upgradeDB($dbVersion) === false) {
                    throw new UpgradeException(
                        __u('Error al aplicar la actualización de la Base de Datos'),
                        UpgradeException::CRITICAL,
                        __u('Compruebe el registro de eventos para más detalles')
                    );
                }
            }
        }

        foreach (self::AUX_UPGRADES as $appVersion) {
            if (Util::checkVersion($version, $appVersion)
                && $this->appUpgrades($appVersion) === false
            ) {
                throw new UpgradeException(
                    __u('Error al aplicar la actualización de la aplicación'),
                    UpgradeException::CRITICAL,
                    __u('Compruebe el registro de eventos para más detalles')
                );
            }
        }

        foreach (self::APP_UPGRADES as $auxVersion) {
            if (Util::checkVersion($version, $auxVersion)
                && $this->auxUpgrades($auxVersion) === false
            ) {
                throw new UpgradeException(
                    __u('Error al aplicar la actualización auxiliar'),
                    UpgradeException::CRITICAL,
                    __u('Compruebe el registro de eventos para más detalles')
                );
            }
        }

        $this->eventDispatcher->notifyEvent('upgrade.db.end',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualizar BBDD')))
        );

        return true;
    }

    /**
     * Aplicar actualizaciones auxiliares antes de actualizar la BBDD
     *
     * @param $version
     * @return bool
     * @throws UpgradeException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function auxPreDbUpgrade($version)
    {
        switch ($version) {
            case '130.16011001':
                debugLog(__FUNCTION__ . ': ' . $version);

                return $this->upgradeDB('130.00000000');
            case '130.16100601':
                debugLog(__FUNCTION__ . ': ' . $version);

                return
                    Account::fixAccountsId()
                    && UserUpgrade::fixUsersId(Request::analyze('userid', 0))
                    && Group::fixGroupId(Request::analyze('groupid', 0))
                    && Profile::fixProfilesId(Request::analyze('profileid', 0))
                    && Category::fixCategoriesId(Request::analyze('categoryid', 0))
                    && Customer::fixCustomerId(Request::analyze('customerid', 0));
        }

        return true;
    }

    /**
     * Actualiza la BBDD según la versión.
     *
     * @param int $version con la versión a actualizar
     * @returns bool
     * @throws UpgradeException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function upgradeDB($version)
    {
        $this->eventDispatcher->notifyEvent('upgrade.db.process',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('Versión'), $version))
        );

        $queries = $this->getQueriesFromFile($version);

        if (count($queries) === 0 || Util::checkVersion(self::$currentDbVersion, $version) === false) {
            debugLog(__('No es necesario actualizar la Base de Datos.'));

            $this->eventDispatcher->notifyEvent('upgrade.db.process',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('No es necesario actualizar la Base de Datos.')))
            );

            return true;
        }

//        TaskFactory::$Message->setTask(__('Actualizar BBDD'));
//        TaskFactory::$Message->setMessage(sprintf('%s : %s', __('Versión'), $version));
//        TaskFactory::update();

        foreach ($queries as $query) {
            try {
                $queryData = new QueryData();
                $queryData->setQuery($query);
                DbWrapper::getQuery($queryData, $this->db);
            } catch (\Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Error al aplicar la actualización de la Base de Datos'))
                        ->addDetail('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode())))
                );

                throw new UpgradeException(__u('Error al aplicar la actualización de la Base de Datos'));
            }
        }

        $this->configService->save('version', $version);

        self::$currentDbVersion = $version;


        $this->eventDispatcher->notifyEvent('upgrade.db.process', new Event($this, EventMessage::factory()
            ->addDescription(__u('Actualización de la Base de Datos realizada correctamente.'))));

        return true;
    }

    /**
     * Obtener las consultas de actualización desde un archivo
     *
     * @param $filename
     * @return array|bool
     */
    private function getQueriesFromFile($filename)
    {
        $file = SQL_PATH . DIRECTORY_SEPARATOR . str_replace('.', '', $filename) . '.sql';

        $queries = [];

        if (file_exists($file) && $handle = fopen($file, 'rb')) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");

                if (strlen(trim($buffer)) > 0) {
                    $queries[] = str_replace("\n", '', $buffer);
                }
            }
        }

        return $queries;
    }

    /**
     * Actualizaciones de la aplicación
     *
     * @param $version
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    private function appUpgrades($version)
    {
        switch ($version) {
            case '210.17022601':
                $dbResult = true;

                if (Util::checkVersion(self::$currentDbVersion, $version)) {
                    $dbResult = $this->upgradeDB($version);
                }

                $masterPass = Request::analyzeEncrypted('masterkey');
                $UserData = User::getItem()->getByLogin(Request::analyze('userlogin'));

                if (!is_object($UserData)) {
                    throw new SPException(__('Error al obtener los datos del usuario', false), SPException::ERROR);
                }

                CoreSession::setUserData($UserData);

                return $dbResult === true
                    && !empty($masterPass)
                    && Crypt::migrate($masterPass);
        }

        return false;
    }

    /**
     * Aplicar actualizaciones auxiliares.
     *
     * @param $version int El número de versión
     * @return bool
     */
    private function auxUpgrades($version)
    {
        try {
            switch ($version) {
                case '120.01':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return (ProfileUtil::migrateProfiles() && UserMigrate::migrateUsersGroup());
                case '120.02':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return UserMigrate::setMigrateUsers();
                case '200.17010901':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return CustomFieldsUtil::migrateCustomFields() && UserPreferencesUtil::migrate();
                case '200.17011202':
                    debugLog(__FUNCTION__ . ': ' . $version);

                    return UserPreferencesUtil::migrate();
            }
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
        $this->db = $this->dic->get(Database::class);
    }
}