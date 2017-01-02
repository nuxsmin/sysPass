<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Plugins;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\DataModel\PluginData;
use SP\Mgmt\ItemBase;

/**
 * Class CategoryBase
 *
 * @package SP\Mgmt\Categories
 */
abstract class PluginBase extends ItemBase
{
    /** @var PluginData */
    protected $itemData;

    /**
     * Plugin constructor.
     *
     * @param $itemData
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData = null)
    {
        if (!$this->dataModel) {
            $this->setDataModel(PluginData::class);
        }

        parent::__construct($itemData);
    }

    /**
     * Devolver los datos del elemento
     * @return PluginData
     */
    public function getItemData()
    {
        return parent::getItemData();
    }
}