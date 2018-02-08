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

namespace SP\Storage;

use PDO;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Traits\InjectableTrait;

defined('APP_ROOT') || die();

/**
 * Class MySQLHandler
 *
 * Esta clase se encarga de crear las conexiones a la BD
 */
class MySQLHandler implements DBStorageInterface
{
    use InjectableTrait;

    const STATUS_OK = 0;
    const STATUS_KO = 1;
    /**
     * @var ConfigData
     */
    protected $ConfigData;
    /**
     * @var Config
     */
    protected $Config;
    /**
     * @var PDO
     */
    private $db;
    /**
     * @var string
     */
    private $dbHost = '';
    /**
     * @var string
     */
    private $dbSocket;
    /**
     * @var int
     */
    private $dbPort = 0;
    /**
     * @var string
     */
    private $dbName = '';
    /**
     * @var string
     */
    private $dbUser = '';
    /**
     * @var string
     */
    private $dbPass = '';
    /**
     * @var int
     */
    private $dbStatus = self::STATUS_KO;

    /**
     * MySQLHandler constructor.
     */
    public function __construct()
    {
        $this->injectDependencies();
        $this->setConnectionData();
    }

    /**
     * Establecer datos de conexión
     */
    public function setConnectionData()
    {
        $this->dbHost = $this->ConfigData->getDbHost();
        $this->dbSocket = $this->ConfigData->getDbSocket();
        $this->dbUser = $this->ConfigData->getDbUser();
        $this->dbPass = $this->ConfigData->getDbPass();
        $this->dbName = $this->ConfigData->getDbName();
        $this->dbPort = $this->ConfigData->getDbPort();
    }

    /**
     * Devuelve el estado de conexión a la BBDD
     * OK -> 0
     * KO -> 1
     *
     * @return int
     */
    public function getDbStatus()
    {
        return $this->dbStatus;
    }

    /**
     * Realizar la conexión con la BBDD.
     * Esta función utiliza PDO para conectar con la base de datos.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return PDO
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */

    public function getConnection()
    {
        if (!$this->db) {
            $isInstalled = $this->ConfigData->isInstalled();

            if (empty($this->dbHost) || empty($this->dbUser) || empty($this->dbPass) || empty($this->dbName)) {
                if ($isInstalled) {
                    Init::initError(__('No es posible conectar con la BD'), __('Compruebe los datos de conexión'));
                } else {
                    throw new SPException(__('No es posible conectar con la BD', false), SPException::CRITICAL, __('Compruebe los datos de conexión', false));
                }
            }

            try {
                $opts = [PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

                if (empty($this->dbSocket)) {
                    $dsn = 'mysql:host=' . $this->dbHost . ';port=' . $this->dbPort . ';dbname=' . $this->dbName . ';charset=utf8';
                } else {
                    $dsn = 'mysql:unix_socket=' . $this->dbSocket . ';dbname=' . $this->dbName . ';charset=utf8';
                }

                $this->db = new PDO($dsn, $this->dbUser, $this->dbPass, $opts);
                $this->dbStatus = self::STATUS_OK;
            } catch (\Exception $e) {
                if ($isInstalled) {
                    if ($e->getCode() === 1049) {
                        $this->ConfigData->setInstalled(false);
                        $this->Config->saveConfig($this->ConfigData);
                    }
                    Init::initError(
                        __('No es posible conectar con la BD'),
                        'Error ' . $e->getCode() . ': ' . $e->getMessage());
                } else {
                    throw new SPException($e->getMessage(), SPException::CRITICAL, $e->getCode());
                }
            }
        }

        return $this->db;
    }

    /**
     * @param Config $config
     */
    public function inject(Config $config)
    {
        $this->Config = $config;
        $this->ConfigData = $config->getConfigData();
    }
}