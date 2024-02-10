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

namespace SP\Domain\Core;

use SP\Domain\Core\Exceptions\CheckException;

/**
 * Class PhpExtensionCheckerInterface
 *
 * @method bool checkCurl(bool $exception = false)
 * @method bool checkLdap(bool $exception = false)
 * @method bool checkPhar(bool $exception = false)
 * @method bool checkGd(bool $exception = false)
 */
interface PhpExtensionCheckerService
{
    /**
     * Checks if the extension is installed
     *
     * @param string $extension
     * @param bool $exception Throws an exception if the extension is not available
     *
     * @return bool
     * @throws CheckException
     */
    public function checkIsAvailable(string $extension, bool $exception = false): bool;

    /**
     * @throws CheckException
     */
    public function checkMandatory(): void;

    /**
     * Returns missing extensions
     */
    public function getMissing(): array;
}