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

namespace SP\Domain\Common\Providers;

use Defuse\Crypto\Core;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use Random\RandomException;

/**
 * Class Password
 */
final class Password
{
    private const CHARS                  = 'abcdefghijklmnopqrstuwxyz';
    private const CHARS_SPECIAL          = '@$%&/()!_:.;{}^-';
    private const CHARS_NUMBER           = '0123456789';
    public const  FLAG_PASSWORD_NUMBER   = 2;
    public const  FLAG_PASSWORD_SPECIAL  = 4;
    public const  FLAG_PASSWORD_STRENGTH = 8;

    /**
     * Generate a ramdom password
     *
     * @param int $length Password length
     * @param int|null $flags Password chars included and checking strength flags
     *
     * @return string
     * @throws RandomException
     */
    public static function randomPassword(int $length = 16, int $flags = null): string
    {
        if ($flags === null) {
            $flags = self::FLAG_PASSWORD_SPECIAL | self::FLAG_PASSWORD_NUMBER | self::FLAG_PASSWORD_STRENGTH;
        }

        $useSpecial = ($flags & self::FLAG_PASSWORD_SPECIAL) > 0;
        $useNumbers = ($flags & self::FLAG_PASSWORD_NUMBER) > 0;

        $alphabet = self::CHARS . strtoupper(self::CHARS);

        if ($useSpecial) {
            $alphabet .= self::CHARS_SPECIAL;
        }

        if ($useNumbers) {
            $alphabet .= self::CHARS_NUMBER;
        }

        /**
         * @throws Exception
         */
        $passGen = static function () use ($alphabet, $length): array {
            $pass = [];
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache

            for ($i = 0; $i < $length; $i++) {
                $n = random_int(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }

            return $pass;
        };

        if ($flags & self::FLAG_PASSWORD_STRENGTH) {
            do {
                $pass = $passGen();
                $strength = self::checkStrength($pass);

                $res = $strength['lower'] > 0 && $strength['upper'] > 0;

                if ($useSpecial === true) {
                    $res = $res && $strength['special'] > 0;
                }

                if ($useNumbers === true) {
                    $res = $res && $strength['number'] > 0;
                }
            } while ($res === false);

            return implode('', $pass);
        }

        return implode($passGen());
    }

    public static function checkStrength(array $pass): array
    {
        $charsUpper = strtoupper(self::CHARS);
        $strength = ['lower' => 0, 'upper' => 0, 'special' => 0, 'number' => 0];

        foreach ($pass as $char) {
            $strength['lower'] += substr_count(self::CHARS, $char);
            $strength['upper'] += substr_count($charsUpper, $char);
            $strength['special'] += substr_count(self::CHARS_SPECIAL, $char);
            $strength['number'] += substr_count(self::CHARS_NUMBER, $char);
        }

        return $strength;
    }

    /**
     * Generar una cadena aleatoria usuando criptografía.
     *
     * @param int $length opcional, con la longitud de la cadena
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function generateRandomBytes(int $length = 30): string
    {
        return Encoding::binToHex(Core::secureRandom($length));
    }
}
