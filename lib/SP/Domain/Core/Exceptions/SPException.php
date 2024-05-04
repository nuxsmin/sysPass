<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Extender la clase Exception para mostrar ayuda en los mensajes
 */
class SPException extends Exception
{
    public const CRITICAL = 1;
    public const WARNING  = 2;
    public const ERROR    = 3;
    public const INFO     = 4;

    /**
     * SPException constructor.
     *
     * @param string $message
     * @param int $type
     * @param string|null $hint
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        string            $message,
        protected int     $type = self::ERROR,
        protected ?string $hint = null,
        int               $code = 0,
        Exception         $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function from(Throwable $throwable, Type $type = Type::ERROR): static
    {
        return new static($throwable->getMessage(), $type->value, null, $throwable->getCode(), $throwable);
    }

    public static function error(
        string    $message,
        ?string   $hint = null,
        int       $code = 0,
        Exception $previous = null
    ): static {
        return new static($message, SPException::ERROR, $hint, $code, $previous);
    }

    public static function critical(
        string    $message,
        ?string   $hint = null,
        int       $code = 0,
        Exception $previous = null
    ): static {
        return new static($message, SPException::CRITICAL, $hint, $code, $previous);
    }

    public static function warning(
        string    $message,
        ?string   $hint = null,
        int       $code = 0,
        Exception $previous = null
    ): static {
        return new static($message, SPException::WARNING, $hint, $code, $previous);
    }

    public static function info(
        string    $message,
        ?string   $hint = null,
        int       $code = 0,
        Exception $previous = null
    ): static {
        return new static($message, SPException::INFO, $hint, $code, $previous);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s: [%s]: %s (%s)', __CLASS__, $this->code, $this->message, $this->hint);
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
