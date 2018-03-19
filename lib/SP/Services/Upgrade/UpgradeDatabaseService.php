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
class UpgradeDatabaseService extends Service implements UpgradeInterface
{
    /**
     * @var array Versiones actualizables
     */
    const UPGRADES = ['300.18010101'];

    /**
     * @var Database
     */
    protected $db;

    /**
     * Check if it needs to be upgraded
     *
     * @param $version
     * @return bool
     */
    public static function needsUpgrade($version)
    {
        return empty($version) || Util::checkVersion($version, self::UPGRADES);
    }

    /**
     * Inicia el proceso de actualización de la BBDD.
     *
     * @param int $version con la versión de la BBDD actual
     * @return bool
     * @throws UpgradeException
     */
    public function upgrade($version)
    {
        $this->eventDispatcher->notifyEvent('upgrade.db.start',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualizar BBDD')))
        );

        $configData = $this->config->getConfigData();

        foreach (self::UPGRADES as $upgradeVersion) {
            if (Util::checkVersion($version, $upgradeVersion)) {
                if ($this->applyPreUpgrade($upgradeVersion) === false) {
                    throw new UpgradeException(
                        __u('Error al aplicar la actualización auxiliar'),
                        UpgradeException::CRITICAL,
                        __u('Compruebe el registro de eventos para más detalles')
                    );
                }

                if ($this->applyUpgrade($upgradeVersion) === false) {
                    throw new UpgradeException(
                        __u('Error al aplicar la actualización de la Base de Datos'),
                        UpgradeException::CRITICAL,
                        __u('Compruebe el registro de eventos para más detalles')
                    );
                }

                debugLog('DB Upgrade: ' . $upgradeVersion);

                $configData->setDatabaseVersion($upgradeVersion);

                $this->config->saveConfig($configData, false);
            }
        }

//        foreach (self::AUX_UPGRADES as $auxVersion) {
//            if (Util::checkVersion($version, $auxVersion)
//                && $this->auxUpgrades($auxVersion) === false
//            ) {
//                throw new UpgradeException(
//                    __u('Error al aplicar la actualización auxiliar'),
//                    UpgradeException::CRITICAL,
//                    __u('Compruebe el registro de eventos para más detalles')
//                );
//            }
//        }

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
     */
    private function applyPreUpgrade($version)
    {
        return true;
    }

    /**
     * Actualiza la BBDD según la versión.
     *
     * @param int $version con la versión a actualizar
     * @returns bool
     * @throws UpgradeException
     */
    private function applyUpgrade($version)
    {
        $queries = $this->getQueriesFromFile($version);

        if (count($queries) === 0) {
            debugLog(__('No es necesario actualizar la Base de Datos.'));

            $this->eventDispatcher->notifyEvent('upgrade.db.process',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('No es necesario actualizar la Base de Datos.')))
            );

            return true;
        }

        foreach ($queries as $query) {
            try {
                $this->eventDispatcher->notifyEvent('upgrade.db.process',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Versión'), $version))
                );

                $queryData = new QueryData();
                $queryData->setQuery($query);
                DbWrapper::getQuery($queryData, $this->db);
            } catch (\Exception $e) {
                processException($e);
                debugLog('SQL: ' . $query);

                $this->eventDispatcher->notifyEvent('exception',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Error al aplicar la actualización de la Base de Datos'))
                        ->addDetail('ERROR', sprintf('%s (%s)', $e->getMessage(), $e->getCode())))
                );

                throw new UpgradeException(__u('Error al aplicar la actualización de la Base de Datos'));
            }
        }

        $this->eventDispatcher->notifyEvent('upgrade.db.process',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Actualización de la Base de Datos realizada correctamente.')))
        );

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

        if (file_exists($file)
            && $handle = fopen($file, 'rb')
        ) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");

                if (strlen(trim($buffer)) > 0 && strpos($buffer, '--') !== 0) {
                    $queries[] = $buffer;
                }
            }
        }

        return $queries;
    }

    protected function initialize()
    {
        $this->db = $this->dic->get(Database::class);
    }
}