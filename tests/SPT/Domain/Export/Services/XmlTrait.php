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

namespace SPT\Domain\Export\Services;

use DOMNodeList;

/**
 * Trait XmlTrait
 *
 * @method assertEquals(mixed $expected, mixed $current)
 */
trait XmlTrait
{
    private function checkNodes(DOMNodeList $nodeList, array $nodes): void
    {
        $names = array_keys($nodes);
        $values = array_values($nodes);

        foreach ($names as $key => $nodeName) {
            $this->assertEquals($nodeName, $nodeList->item($key)->nodeName);

            if (is_callable($values[$key])) {
                $values[$key]($nodeList->item($key)->nodeValue);
            } else {
                $this->assertEquals($values[$key], $nodeList->item($key)->nodeValue);
            }
        }
    }
}
