<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
    public const OK       = 0;
    public const CRITICAL = 1;
    public const WARNING  = 2;
    public const ERROR    = 3;
    public const INFO     = 4;

    protected int     $type;
    protected ?string $hint;

    /**
     * SPException constructor.
     *
     * @param  string  $message
     * @param  int  $type
     * @param  string|null  $hint
     * @param  int  $code
     * @param  Exception|null  $previous
     */
    public function __construct(
        string $message,
        int $type = self::ERROR,
        ?string $hint = null,
        int $code = 0,
        Exception $previous = null
    ) {
        $this->type = $type;
        $this->hint = $hint;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * @return int|string
     */
    public function getType(): int|string
    {
        return $this->type;
    }
}
