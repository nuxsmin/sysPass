<?php
declare(strict_types=1);
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

namespace SP\Domain\Image\Ports;

use SP\Domain\Core\Exceptions\InvalidImageException;

/**
 * Interface ImageService
 */
interface ImageService
{
    /**
     * Build a thumbnail form an image
     *
     * @param string $image A raw image
     * @return string A base64 encode image string
     * @throws InvalidImageException
     */
    public function createThumbnail(string $image): string;

    /**
     * Convert a test into an image
     *
     * @param string $text
     *
     * @return false|string
     */
    public function convertText(string $text): bool|string;
}
