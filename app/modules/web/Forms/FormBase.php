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

namespace SP\Modules\Web\Forms;

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Http\Ports\RequestService;

/**
 * Class FormBase
 *
 * @package SP\Modules\Web\Forms
 */
abstract class FormBase
{
    protected ConfigFileService $config;
    protected ConfigDataInterface $configData;
    protected Context           $context;

    /**
     * FormBase constructor.
     *
     * @param ConfigFileService $config
     * @param Context $context
     * @param RequestService $request
     * @param int|null $itemId
     */
    public function __construct(
        ConfigFileService                 $config,
        Context                           $context,
        protected readonly RequestService $request,
        protected ?int                    $itemId = null
    ) {
        $this->config = $config;
        $this->configData = $this->config->getConfigData();
        $this->context = $context;
    }

    /**
     * @return int|null
     */
    public function getItemId(): ?int
    {
        return $this->itemId;
    }
}
