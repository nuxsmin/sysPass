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

use SP\Controller\ActionsInterface;

abstract class CustomFieldsBase
{
    const TYPE_TEXT = 1;
    const TYPE_PASSWORD = 2;
    const TYPE_DATE = 3;
    const TYPE_NUMBER = 4;
    /**
     * @var string
     */
    protected $_name = '';
    /**
     * @var int
     */
    protected $_type = 0;
    /**
     * @var int
     */
    protected $_module = 0;
    /**
     * @var int
     */
    protected $_id = 0;
    /**
     * @var bool
     */
    protected $_required = false;
    /**
     * @var string
     */
    private $_help = '';

    public static function getFieldsTypes($typeId = null)
    {
        $types = array(
            self::TYPE_TEXT => 'text',
            self::TYPE_PASSWORD => 'password',
            self::TYPE_DATE => 'date',
            self::TYPE_NUMBER => 'number'
        );

        if (!is_null($typeId)) {
            return $types[$typeId];
        }

        return $types;
    }

    public static function getFieldsModules($moduleId = null)
    {
        $modules = array(
            ActionsInterface::ACTION_ACC_NEW => _('Cuentas'),
            ActionsInterface::ACTION_MGM_CATEGORIES => _('Categorías'),
            ActionsInterface::ACTION_MGM_CUSTOMERS => _('Clientes'),
            ActionsInterface::ACTION_USR_USERS => _('Usuarios'),
            ActionsInterface::ACTION_USR_GROUPS => _('Grupos')

        );

        if (!is_null($moduleId)) {
            return $modules[$moduleId];
        }

        return $modules;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->_required;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->_required = $required;
    }

    /**
     * @return int
     */
    public function getModule()
    {
        return $this->_module;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->_help;
    }

    /**
     * @param string $help
     */
    public function setHelp($help)
    {
        $this->_help = $help;
    }
}