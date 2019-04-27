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

namespace SP\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Key;
use SessionHandler;

/**
 * Class CryptSessionHandler
 *
 * @package SP\Core\Crypt
 */
final class CryptSessionHandler extends SessionHandler
{
    /**
     * @var bool Indica si la sesión está encriptada
     */
    public static $isSecured = false;
    /**
     * @var Key
     */
    private $key;

    /**
     * Session constructor.
     *
     * @param Key $Key
     */
    public function __construct(Key $Key)
    {
        $this->key = $Key;
    }

    /**
     * Read session data
     *
     * @link  http://php.net/manual/en/sessionhandler.read.php
     *
     * @param string $id The session id to read data for.
     *
     * @return string <p>
     *                           Returns an encoded string of the read data.
     *                           If nothing was read, it must return an empty string.
     *                           Note this value is returned internally to PHP for processing.
     *                           </p>
     * @since 5.4.0
     */
    public function read($id)
    {
        $data = parent::read($id);

        if (!$data) {
            return '';
        } else {
            try {
                self::$isSecured = true;

                return Crypt::decrypt($data, $this->key);
            } catch (CryptoException $e) {
                self::$isSecured = false;

                logger($e->getMessage());
                logger('Session data not encrypted.');

                return $data;
            }
        }
    }

    /**
     * Write session data
     *
     * @link  http://php.net/manual/en/sessionhandler.write.php
     *
     * @param string $id           The session id.
     * @param string $data         <p>
     *                             The encoded session data. This data is the
     *                             result of the PHP internally encoding
     *                             the $_SESSION superglobal to a serialized
     *                             string and passing it as this parameter.
     *                             Please note sessions use an alternative serialization method.
     *                             </p>
     *
     * @return bool <p>
     *                             The return value (usually TRUE on success, FALSE on failure).
     *                             Note this value is returned internally to PHP for processing.
     *                             </p>
     * @since 5.4.0
     */
    public function write($id, $data)
    {
        try {
            $data = Crypt::encrypt($data, $this->key);

            self::$isSecured = true;
        } catch (CryptoException $e) {
            self::$isSecured = false;

            logger('Could not encrypt session data.');
            logger($e->getMessage());
        }

        return parent::write($id, $data);
    }
}