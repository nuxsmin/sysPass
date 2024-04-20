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

namespace SP\Domain\Common\Adapters;

use League\Fractal\TransformerAbstract;
use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Common\Models\Model;
use SP\Domain\Config\Ports\ConfigDataInterface;

/**
 * Class Adapter
 */
abstract class Adapter extends TransformerAbstract
{
    public function __construct(
        protected readonly ConfigDataInterface $configData,
        protected readonly string              $baseUrl
    ) {
    }

    abstract public function transform(Model&Dto $data);
}
