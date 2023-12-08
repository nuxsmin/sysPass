<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;

/**
 * Trait EncryptedModel
 */
trait EncryptedModel
{
    protected ?string $key = null;

    /**
     * @param string $key
     * @param CryptInterface $crypt
     * @param string $property
     *
     * @return EncryptedModel
     * @throws CryptException
     * @throws NoSuchPropertyException
     */
    public function encrypt(string $key, CryptInterface $crypt, string $property = 'data'): static
    {
        if (property_exists($this, $property)) {
            if ($this->{$property} === null) {
                return $this;
            }

            $this->key = $crypt->makeSecuredKey($key);

            $this->{$property} = $crypt->encrypt($this->{$property}, $this->key, $key);

            return $this;
        }

        throw new NoSuchPropertyException($property);
    }

    /**
     * @param string $key
     * @param CryptInterface $crypt
     * @param string $property
     *
     * @return EncryptedModel
     * @throws CryptException
     * @throws NoSuchPropertyException
     */
    public function decrypt(string $key, CryptInterface $crypt, string $property = 'data'): static
    {
        if (property_exists($this, $property) && !empty($this->key)) {
            if ($this->{$property} === null) {
                return $this;
            }

            $this->{$property} = $crypt->decrypt($this->{$property}, $this->key, $key);

            return $this;
        }

        throw new NoSuchPropertyException($property);
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
