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

namespace SP\Mgmt;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;

/**
 * Class CustomFieldsBase para la definición de campos personalizados
 *
 * @package SP
 */
abstract class CustomFieldsBase
{
    /**
     * Tipos de Campos
     */
    const TYPE_TEXT = 1;
    const TYPE_PASSWORD = 2;
    const TYPE_DATE = 3;
    const TYPE_NUMBER = 4;
    const TYPE_EMAIL = 5;
    const TYPE_TELEPHONE = 6;
    const TYPE_URL = 7;
    const TYPE_COLOR = 8;
    const TYPE_WIKI = 9;

    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var int
     */
    protected $type = 0;
    /**
     * @var int
     */
    protected $module = 0;
    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var bool
     */
    protected $required = false;
    /**
     * @var string
     */
    private $help = '';

    /**
     * Devolver los tipos de campos soportados
     *
     * @param int  $typeId El tipo de campo
     * @param bool $nice Devolver en formato "bonito"
     * @return array
     */
    public static function getFieldsTypes($typeId = null, $nice = false)
    {
        $types = array(
            self::TYPE_TEXT => array('text', _('Texto')),
            self::TYPE_WIKI => array('text', _('Wiki')),
            self::TYPE_PASSWORD => array('password', _('Clave')),
            self::TYPE_DATE => array('date', _('Fecha')),
            self::TYPE_NUMBER => array('number', _('Número')),
            self::TYPE_EMAIL => array('email', _('Email')),
            self::TYPE_TELEPHONE => array('tel', _('Teléfono')),
            self::TYPE_URL => array('url', _('Link')),
            self::TYPE_COLOR => array('color', _('Color'))
        );

        if (!is_null($typeId)) {
            return ($nice === false) ? $types[$typeId][0] : $types[$typeId][1];
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
        return $this->required;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return int
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param string $help
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }
}