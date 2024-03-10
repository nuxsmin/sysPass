<?php
/*
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

namespace SP\DataModel;

use ReflectionClass;
use SP\Domain\Common\Attributes\Encryptable;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;

/**
 * Trait EncryptedModel
 */
trait EncryptedModel
{
    protected ?string $key = null;

    /**
     * Encrypt the encryptable property and returns a new object with the encrypted property and key
     *
     * @param string $password
     * @param CryptInterface $crypt
     *
     * @return EncryptedModel
     * @throws CryptException
     */
    public function encrypt(string $password, CryptInterface $crypt): static
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getAttributes(Encryptable::class) as $attribute) {
            /** @var Encryptable $instance */
            $instance = $attribute->newInstance();

            $data = $this->{$instance->getDataProperty()};

            if ($data !== null) {
                $key = $crypt->makeSecuredKey($password);

                return $this->mutate([
                                         $instance->getKeyProperty() => $key,
                                         $instance->getDataProperty() => $crypt->encrypt(
                                             $data,
                                             $key,
                                             $password
                                         )
                                     ]);
            }
        }

        return $this;
    }

    /**
     * Decrypt the encryptable property and returns a new object with the decryped property and key
     *
     * @param string $password
     * @param CryptInterface $crypt
     *
     * @return EncryptedModel
     * @throws CryptException
     */
    public function decrypt(string $password, CryptInterface $crypt): static
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getAttributes(Encryptable::class) as $attribute) {
            /** @var Encryptable $instance */
            $instance = $attribute->newInstance();

            $data = $this->{$instance->getDataProperty()};
            $key = $this->{$instance->getKeyProperty()};

            if ($data !== null && $key !== null) {
                return $this->mutate([
                                         $instance->getDataProperty() => $crypt->decrypt(
                                             $data,
                                             $key,
                                             $password
                                         )
                                     ]);
            }
        }

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
