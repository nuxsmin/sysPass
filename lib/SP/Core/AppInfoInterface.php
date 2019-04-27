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

namespace SP\Core;


/**
 * Interface AppInfoInterface
 *
 * @package SP\Core
 */
interface AppInfoInterface
{
    const APP_NAME = 'sysPass';
    const APP_DESC = 'Systems Password Manager';
    const APP_ALIAS = 'SPM';
    const APP_WEBSITE_URL = 'https://www.syspass.org';
    const APP_BLOG_URL = 'https://www.cygnux.org';
    const APP_DOC_URL = 'https://doc.syspass.org';
    const APP_UPDATES_URL = 'https://api.github.com/repos/nuxsmin/sysPass/releases/latest';
    const APP_NOTICES_URL = 'https://api.github.com/repos/nuxsmin/sysPass/issues?milestone=none&state=open&labels=Notices';
    const APP_ISSUES_URL = 'https://github.com/nuxsmin/sysPass/issues';
}