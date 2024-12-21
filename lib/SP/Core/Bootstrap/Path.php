<?php
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

namespace SP\Core\Bootstrap;

/**
 * Enum Path
 */
enum Path
{
    case APP;
    case SQL;
    case PUBLIC;
    case XML_SCHEMA;
    case RESOURCES;
    case MODULES;
    case LOCALES;
    case CONFIG;
    case CONFIG_FILE;
    case ACTIONS_FILE;
    case MIMETYPES_FILE;
    case SQL_FILE;
    case VIEW;
    case BACKUP;
    case CACHE;
    case TMP;
    case LOG_FILE;
    case PLUGINS;
}
