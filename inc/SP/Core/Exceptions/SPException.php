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

namespace SP\Core\Exceptions;

use Exception;

defined('APP_ROOT') || die();

/**
 * Extender la clase Exception para mostrar ayuda en los mensajes
 */
class SPException extends Exception
{
    /**
     * Constantes para tipos de excepción
     */
    const SP_OK = 0;
    const SP_CRITICAL = 1;
    const SP_WARNING = 2;
    const SP_ERROR = 3;
    const SP_INFO = 4;
    /**
     * @var int Tipo de excepción
     */
    protected $type = 0;
    /**
     * @var string Ayuda de la excepción
     */
    protected $hint = '';

    /**
     * SPException constructor.
     *
     * @param string         $type
     * @param string         $message
     * @param string         $hint
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($type, $message, $hint = '', $code = 0, \Exception $previous = null)
    {
        $this->type = $type;
        $this->hint = $hint;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param $type
     * @return mixed
     */
    public static function getExceptionTypeName($type)
    {
        $typeName = [
            self::SP_OK => 'ok',
            self::SP_CRITICAL => 'critical',
            self::SP_WARNING => 'warning',
            self::SP_ERROR => 'error'
        ];

        return $typeName[$type];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    /**
     * @return string
     */
    public function getHint()
    {
        return $this->hint;
    }

    /**
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }
}