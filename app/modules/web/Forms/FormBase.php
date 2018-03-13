<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Forms;

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Dic\InjectableTrait;

/**
 * Class FormBase
 *
 * @package SP\Modules\Web\Forms
 */
abstract class FormBase
{
    use InjectableTrait;

    /**
     * @var int
     */
    protected $itemId;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var SessionContext
     */
    protected $context;

    /**
     * FormBase constructor.
     *
     * @param $itemId
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct($itemId = null)
    {
        $this->injectDependencies();

        $this->itemId = $itemId;
    }

    /**
     * @param Config           $config
     * @param ContextInterface $session
     */
    public function inject(Config $config, ContextInterface $session)
    {
        $this->config = $config;
        $this->configData = $config->getConfigData();
        $this->context = $session;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    abstract protected function analyzeRequestData();
}