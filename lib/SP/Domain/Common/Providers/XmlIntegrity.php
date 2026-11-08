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

namespace SP\Domain\Common\Providers;

use DOMDocument;
use DOMNode;
use DOMXPath;
use SP\Domain\Crypt\Ports\HashService;

/**
 * Integrity checks for the sysPass XML export format.
 */
final class XmlIntegrity
{
    public static function generateHash(DOMDocument $document): string
    {
        return sha1(
            array_reduce(
                iterator_to_array((new DOMXPath($document))->query('/Root/*[not(self::Meta)]')->getIterator()),
                static fn(string $carry, DOMNode $node) => $carry . $document->saveXML($node),
                ''
            )
        );
    }

    public static function checkHash(DOMDocument $document, string $key, HashService $hashService): bool
    {
        $xpath = new DOMXPath($document);
        $hash = $xpath->query('/Root/Meta/Hash')->item(0)?->nodeValue;
        $sign = $xpath->query('/Root/Meta/Hash/@sign')->item(0)?->nodeValue;

        if (!empty($hash) && !empty($sign)) {
            return $hashService->checkMessage($hash, $key, $sign);
        }

        return $hash !== null && $hash === self::generateHash($document);
    }
}
