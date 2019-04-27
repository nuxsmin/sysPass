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

use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;

defined('APP_ROOT') || die();

/**
 * Clase con utilizades para la aplicación
 */
final class Util
{

    /**
     * Comprueba y devuelve un directorio temporal válido
     *
     * @return bool|string
     */
    public static function getTempDir()
    {
        $sysTmp = sys_get_temp_dir();
        $file = 'syspass.test';

        $checkDir = function ($dir) use ($file) {
            if (file_exists($dir . DIRECTORY_SEPARATOR . $file)) {
                return $dir;
            }

            if (is_dir($dir) || @mkdir($dir)) {
                if (touch($dir . DIRECTORY_SEPARATOR . $file)) {
                    return $dir;
                }
            }

            return false;
        };

        if ($checkDir(TMP_PATH)) {
            return TMP_PATH;
        }

        return $checkDir($sysTmp);
    }

    /**
     * Realiza el proceso de logout.
     *
     * FIXME
     */
    public static function logout()
    {
        exit('<script>sysPassApp.actions.main.logout();</script>');
    }

    /**
     * Obtener el tamaño máximo de subida de PHP.
     */
    public static function getMaxUpload(): int
    {
        return min(self::convertShortUnit(ini_get('upload_max_filesize')),
            self::convertShortUnit(ini_get('post_max_size')),
            self::convertShortUnit(ini_get('memory_limit')));
    }

    /**
     * @param $value
     *
     * @return int
     */
    public static function convertShortUnit($value): int
    {
        if (preg_match('/(\d+)(\w+)/', $value, $match)) {
            switch (strtoupper($match[2])) {
                case 'K':
                    return (int)$match[1] * 1024;
                case 'M':
                    return (int)$match[1] * pow(1024, 2);
                case 'G':
                    return (int)$match[1] * pow(1024, 3);
            }
        }

        return (int)$value;
    }

    /**
     * Checks a variable to see if it should be considered a boolean true or false.
     * Also takes into account some text-based representations of true of false,
     * such as 'false','N','yes','on','off', etc.
     *
     * @param mixed $in     The variable to check
     * @param bool  $strict If set to false, consider everything that is not false to
     *                      be true.
     *
     * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
     * @author Samuel Levy <sam+nospam@samuellevy.com>
     *
     */
    public static function boolval($in, $strict = false)
    {
        $in = is_string($in) ? strtolower($in) : $in;

        // if not strict, we only have to check if something is false
        if (in_array($in, ['false', 'no', 'n', '0', 'off', false, 0], true)
            || !$in
        ) {
            return false;
        }

        if ($strict
            && in_array($in, ['true', 'yes', 'y', '1', 'on', true, 1], true)
        ) {
            return true;
        }

        // not strict? let the regular php bool check figure it out (will
        // largely default to true)
        return ($in ? true : false);
    }

    /**
     * Cast an object to another class, keeping the properties, but changing the methods
     *
     * @param string        $dstClass Destination class name
     * @param string|object $serialized
     * @param string        $srcClass Old class name for removing from private methods
     *
     * @return mixed
     */
    public static function unserialize($dstClass, $serialized, $srcClass = null)
    {
        if (!is_object($serialized)) {
            $match = preg_match_all('/O:\d+:"(?P<class>[^"]++)"/', $serialized, $matches);

            $process = false;

            if ($match) {
                foreach ($matches['class'] as $class) {
                    if (!class_exists($class)
                        || $class !== $dstClass
                    ) {
                        $process = true;
                    }
                }

                if ($process === false) {
                    return unserialize($serialized);
                }
            }

            // Serialized data needs to be processed to change the class name
            if ($process === true) {
                // If source class is set, it will try to clean up the class name from private methods
                if ($srcClass !== null) {
                    $serialized = preg_replace_callback(
                        '/:\d+:"\x00' . preg_quote($srcClass, '/') . '\x00(\w+)"/',
                        function ($matches) {
                            return ':' . strlen($matches[1]) . ':"' . $matches[1] . '"';
                        },
                        $serialized);
                }

                return self::castToClass($serialized, $dstClass);
            }

            if (preg_match('/a:\d+:{/', $serialized)) {
                return unserialize($serialized);
            }
        }

        return $serialized;
    }

    /**
     * Cast an object to another class
     *
     * @param $cast
     * @param $class
     *
     * @return mixed
     */
    public static function castToClass($cast, $class)
    {
        // TODO: should avoid '__PHP_Incomplete_Class'?

        $cast = is_object($cast) ? serialize($cast) : $cast;

        return unserialize(preg_replace('/O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', $cast));
    }

    /**
     * Bloquear la aplicación
     *
     * @param int    $userId
     * @param string $subject
     *
     * @throws FileException
     */
    public static function lockApp($userId, $subject)
    {
        $data = ['time' => time(), 'userId' => (int)$userId, 'subject' => $subject];

        $file = new FileHandler(LOCK_FILE);
        $file->save(json_encode($data));

        logger('Application locked out');
    }

    /**
     * Desbloquear la aplicación
     *
     * @return bool
     */
    public static function unlockApp()
    {
        logger('Application unlocked');

        return @unlink(LOCK_FILE);
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @return mixed
     */
    public static function getAppLock()
    {
        try {
            $file = new FileHandler(LOCK_FILE);
            return json_decode($file->readToString());
        } catch (FileException $e) {
            return false;
        }
    }

    /**
     * Devolver el tiempo aproximado en segundos de una operación
     *
     * @param $startTime
     * @param $numItems
     * @param $totalItems
     *
     * @return array Con el tiempo estimado y los elementos por segundo
     */
    public static function getETA($startTime, $numItems, $totalItems)
    {
        if ($numItems > 0 && $totalItems > 0) {
            $runtime = time() - $startTime;
            $eta = (int)((($totalItems * $runtime) / $numItems) - $runtime);

            return [$eta, $numItems / $runtime];
        }

        return [0, 0];
    }

    /**
     * Adaptador para convertir una cadena de IDs a un array
     *
     * @param string $itemsId
     * @param string $delimiter
     *
     * @return array
     */
    public static function itemsIdAdapter(string $itemsId, $delimiter = ','): array
    {
        return array_map(function ($value) {
            return intval($value);
        }, explode($delimiter, $itemsId));
    }

    /**
     * @return int
     */
    public static function getMaxDownloadChunk(): int
    {
        return self::convertShortUnit(ini_get('memory_limit')) / FileHandler::CHUNK_FACTOR;
    }
}
