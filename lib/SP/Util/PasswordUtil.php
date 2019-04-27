<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Util;


use Defuse\Crypto\Core;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;

/**
 * Class PasswordUtil
 *
 * @package SP\Util
 */
final class PasswordUtil
{
    const CHARS = 'abcdefghijklmnopqrstuwxyz';
    const CHARS_SPECIAL = '@$%&/()!_:.;{}^-';
    const CHARS_NUMBER = '0123456789';
    const FLAG_PASSWORD_NUMBER = 2;
    const FLAG_PASSWORD_SPECIAL = 4;
    const FLAG_PASSWORD_STRENGTH = 8;

    /**
     * Generate a ramdom password
     *
     * @param int $length Password length
     * @param int $flags  Password chars included and checking strength flags
     *
     * @return string
     */
    public static function randomPassword($length = 16, int $flags = null)
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
         * @return array
         */
        $passGen = function () use ($alphabet, $length) {
            $pass = [];
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache

            for ($i = 0; $i < $length; $i++) {
                $n = mt_rand(0, $alphaLength);
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

    /**
     * @param array $pass
     *
     * @return array
     */
    public static function checkStrength(array $pass)
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
     * @return string
     * @throws EnvironmentIsBrokenException
     */
    public static function generateRandomBytes($length = 30)
    {
        return Encoding::binToHex(Core::secureRandom($length));
    }
}