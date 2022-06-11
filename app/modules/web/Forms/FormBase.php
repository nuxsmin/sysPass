<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Application;
use SP\Core\Context\ContextInterface;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Http\Request;
use SP\Http\RequestInterface;

/**
 * Class FormBase
 *
 * @package SP\Modules\Web\Forms
 */
abstract class FormBase
{
    protected ?int                $itemId;
    protected ConfigFileService   $config;
    protected ConfigDataInterface $configData;
    protected ContextInterface    $context;
    protected Request             $request;

    /**
     * FormBase constructor.
     *
     * @param  \SP\Core\Application  $application
     * @param  \SP\Http\RequestInterface  $request
     * @param  int|null  $itemId
     */
    public function __construct(
        Application $application,
        RequestInterface $request,
        ?int $itemId = null
    ) {
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->context = $application->getContext();
        $this->request = $request;
        $this->itemId = $itemId;
    }

    /**
     * @return int|null
     */
    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    /**
     * Analizar los datos de la petición HTTP
     */
    abstract protected function analyzeRequestData();
}