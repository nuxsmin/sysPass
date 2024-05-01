<?php
declare(strict_types=1);
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

namespace SP\Domain\Core\Bootstrap;

/**
 * Class UriContext
 */
interface UriContextInterface
{
    /**
     * The full URL to reach the application (e.g. https://sub.example.com/syspass/)
     *
     * @return string
     */
    public function getWebUri(): string;

    /**
     * The current request path relative to the application root (e.g. files/index.php)
     *
     * @return string
     */
    public function getWebRoot(): string;

    public function getSubUri(): string;
}
