<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core;


/**
 * Interface AppInfoInterface
 *
 * @package SP\Core
 */
interface AppInfoInterface
{
    public const APP_NAME = 'sysPass';
    public const APP_DESC = 'Systems Password Manager';
    public const APP_ALIAS = 'SPM';
    public const APP_WEBSITE_URL = 'https://www.syspass.org';
    public const APP_BLOG_URL = 'https://www.cygnux.org';
    public const APP_DOC_URL = 'https://doc.syspass.org';
    public const APP_UPDATES_URL = 'https://api.github.com/repos/nuxsmin/sysPass/releases/latest';
    public const APP_NOTICES_URL = 'https://api.github.com/repos/nuxsmin/sysPass/issues?milestone=none&state=open&labels=Notices';
    public const APP_ISSUES_URL = 'https://github.com/nuxsmin/sysPass/issues';
}
