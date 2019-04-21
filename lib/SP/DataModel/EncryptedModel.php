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

namespace SP\DataModel;


use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\NoSuchPropertyException;

/**
 * Trait EncryptedModel
 *
 * @package SP\DataModel
 */
trait EncryptedModel
{
    /**
     * @var string
     */
    private $key;

    /**
     * @param string $key
     * @param string $property
     *
     * @return static|null
     * @throws NoSuchPropertyException
     * @throws CryptoException
     */
    public function encrypt(string $key, string $property = 'data')
    {
        if (property_exists($this, $property)) {
            if ($this->$property === null) {
                return null;
            }

            $this->key = Crypt::makeSecuredKey($key);

            $this->$property = Crypt::encrypt($this->$property, $this->key, $key);

            return $this;
        }

        throw new NoSuchPropertyException($property);
    }

    /**
     * @param string $key
     * @param string $property
     *
     * @return static|null
     * @throws NoSuchPropertyException
     * @throws CryptoException
     */
    public function decrypt(string $key, string $property = 'data')
    {
        if (property_exists($this, $property)
            && !empty($this->key)
        ) {
            if ($this->$property === null) {
                return null;
            }

            $this->$property = Crypt::decrypt($this->$property, $this->key, $key);

            return $this;
        }

        throw new NoSuchPropertyException($property);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}