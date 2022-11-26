<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Install\Adapters;

use SP\Http\RequestInterface;

/**
 * Class InstallDataAdapter
 */
final class InstallDataFactory
{
    public static function buildFromRequest(RequestInterface $request): InstallData
    {
        $installData = new InstallData();
        $installData->setSiteLang($request->analyzeString('sitelang', 'en_US'));
        $installData->setAdminLogin($request->analyzeString('adminlogin', 'admin'));
        $installData->setAdminPass($request->analyzeEncrypted('adminpass'));
        $installData->setMasterPassword($request->analyzeEncrypted('masterpassword'));
        $installData->setDbAdminUser($request->analyzeString('dbuser', 'root'));
        $installData->setDbAdminPass($request->analyzeEncrypted('dbpass'));
        $installData->setDbName($request->analyzeString('dbname', 'syspass'));
        $installData->setDbHost($request->analyzeString('dbhost', 'localhost'));
        $installData->setHostingMode($request->analyzeBool('hostingmode', false));

        return $installData;
    }
}
