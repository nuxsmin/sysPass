<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\CustomField;

/**
 * Class CustomFieldItem
 *
 * @package SP\Services\CustomField
 */
final class CustomFieldItem
{
    public $required = false;
    public $showInList = false;
    public $help;
    public $definitionId = 0;
    public $definitionName;
    public $typeId = 0;
    public $typeName;
    public $typeText;
    public $moduleId = 0;
    public $formId;
    public $value;
    public $isEncrypted;
    public $isValueEncrypted;
}