<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Config\Ports;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Infrastructure\File\FileException;

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
interface ConfigInterface
{
    /**
     * Cargar el archivo de configuración
     *
     * @throws FileException
     */
    public function loadConfigFromFile(): ConfigDataInterface;

    /**
     * Guardar la configuración
     *
     * @param ConfigDataInterface $configData
     * @param bool|null $backup
     *
     * @return ConfigInterface
     * @throws FileException
     */
    public function saveConfig(
        ConfigDataInterface $configData,
        ?bool               $backup = true
    ): ConfigInterface;

    /**
     * Commits a config data
     */
    public function updateConfig(ConfigDataInterface $configData): ConfigInterface;

    /**
     * Cargar la configuración desde el contexto
     */
    public function loadConfig(?bool $reload = false): ConfigDataInterface;

    /**
     * Returns a clone of the configuration data
     *
     * @return ConfigDataInterface
     */
    public function getConfigData(): ConfigDataInterface;

    /**
     * @throws FileException
     * @throws EnvironmentIsBrokenException
     */
    public function generateUpgradeKey(): ConfigInterface;
}
