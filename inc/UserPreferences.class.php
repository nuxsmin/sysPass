<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Class UsersPreferences para la gestion de las preferencias de usuarios
 *
 * @package SP
 */
class UserPreferences
{
    /**
     * @var int
     */
    private $_id = 0;

    /**
     * Usar autentificación en 2 pasos
     *
     * @var bool
     */
    private $_use2Fa = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return boolean
     */
    public function isUse2Fa()
    {
        return $this->_use2Fa;
    }

    /**
     * @param boolean $use2Fa
     */
    public function setUse2Fa($use2Fa)
    {
        $this->_use2Fa = $use2Fa;
    }

    /**
     * Obtener las preferencas de un usuario
     *
     * @param $id int El id del usuario
     * @return bool|UserPreferences
     * @throws SPException
     */
    public static function getPreferences($id)
    {
        $query = 'SELECT user_preferences FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_WARNING, _('Datos de preferencias incorrectos'));
        }

        $preferences = unserialize($queryRes->user_preferences);

        if (!$preferences instanceof UserPreferences) {
            return new UserPreferences();
        }

        return $preferences;
    }

    /**
     * Modificar las preferencias de un usuario
     *
     * @return bool
     */
    public function updatePreferences()
    {
        $query = 'UPDATE usrData SET '
            . 'user_preferences = :preferences '
            . 'WHERE user_id = :id LIMIT 1';

        $data['id'] = $this->getId();
        $data['preferences'] = serialize($this);

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        return true;
    }


}