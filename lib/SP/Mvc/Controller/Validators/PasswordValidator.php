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

namespace SP\Mvc\Controller\Validators;

use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\Password;

/**
 * Class PasswordValidator
 *
 * @package SP\Mvc\Controller
 */
final class PasswordValidator implements ValidatorInterface
{
    /**
     * @var Password
     */
    private $password;

    /**
     * PasswordValidator constructor.
     *
     * @param Password $password
     */
    public function __construct(Password $password)
    {
        $this->password = $password;
    }

    /**
     * @param Password $password
     *
     * @return PasswordValidator
     */
    public static function factory(Password $password)
    {
        return new self($password);
    }

    /**
     * @param string $string
     *
     * @return bool
     * @throws ValidationException
     */
    public function validate(string $string): bool
    {
        if (mb_strlen($string) < $this->password->getLength()) {
            throw new ValidationException(sprintf(__('Password needs to be %d characters long'), $this->password->getLength()));
        }

        $regex = $this->password->getRegex();

        if (!empty($this->password->getRegex()) && !Validator::matchRegex($string, $regex)) {
            throw new ValidationException(__u('Password does not contain the required characters'), ValidationException::ERROR, $regex);
        }

        if ($this->password->isUseLetters()) {
            if (!Validator::hasLetters($string)) {
                throw new ValidationException(__u('Password needs to contain letters'));
            }

            if ($this->password->isUseLower() && !Validator::hasLower($string)) {
                throw new ValidationException(__u('Password needs to contain lower case letters'));
            }

            if ($this->password->isUseUpper() && !Validator::hasUpper($string)) {
                throw new ValidationException(__u('Password needs to contain upper case letters'));
            }
        }

        if ($this->password->isUseNumbers() && !Validator::hasNumbers($string)) {
            throw new ValidationException(__u('Password needs to contain numbers'));
        }

        if ($this->password->isUseSymbols() && !Validator::hasSymbols($string)) {
            throw new ValidationException(__u('Password needs to contain symbols'));
        }

        return true;
    }
}