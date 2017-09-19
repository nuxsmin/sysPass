<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Mgmt\Profiles;

use SP\Core\Exceptions\InvalidClassException;
use SP\DataModel\ProfileData;
use SP\Mgmt\ItemBaseInterface;
use SP\Mgmt\ItemBaseTrait;

defined('APP_ROOT') || die();

/**
 * Clase ProfileBase para la definición de perfiles de acceso de usuarios
 *
 * @package SP
 */
abstract class ProfileBase implements ItemBaseInterface
{
    use ItemBaseTrait;

    /**
     * Inicializar la clase
     *
     * @return void
     * @throws InvalidClassException
     */
    protected function init()
    {
        $this->setDataModel(ProfileData::class);
    }
}