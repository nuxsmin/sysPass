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

namespace SP\Mvc\Controller\Validators;

use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\PresetInterface;

use function SP\__;
use function SP\__u;

/**
 * Class PasswordValidator
 *
 * @package SP\Mvc\Controller
 */
final class PasswordValidator implements ValidatorInterface
{
    /**
     * @throws ValidationException
     */
    public function validate(PresetInterface $preset, string $string): bool
    {
        if (!$preset instanceof Password) {
            throw new ValidationException(__u('Preset not valid for this validator'));
        }

        if (mb_strlen($string) < $preset->getLength()) {
            throw new ValidationException(
                sprintf(
                    __('Password needs to be %d characters long'),
                    $preset->getLength()
                )
            );
        }

        $regex = $preset->getRegex();

        if (!empty($preset->getRegex()) && !Validator::matchRegex($string, $regex)) {
            throw new ValidationException(
                __u('Password does not contain the required characters'),
                SPException::ERROR,
                $regex
            );
        }

        if ($preset->isUseLetters()) {
            if (!Validator::hasLetters($string)) {
                throw new ValidationException(__u('Password needs to contain letters'));
            }

            if ($preset->isUseLower() && !Validator::hasLower($string)) {
                throw new ValidationException(__u('Password needs to contain lower case letters'));
            }

            if ($preset->isUseUpper() && !Validator::hasUpper($string)) {
                throw new ValidationException(__u('Password needs to contain upper case letters'));
            }
        }

        if ($preset->isUseNumbers() && !Validator::hasNumbers($string)) {
            throw new ValidationException(__u('Password needs to contain numbers'));
        }

        if ($preset->isUseSymbols() && !Validator::hasSymbols($string)) {
            throw new ValidationException(__u('Password needs to contain symbols'));
        }

        return true;
    }
}
