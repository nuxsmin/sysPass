<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Mgmt\Files;

use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class FileUtil
 *
 * @package SP\Mgmt\Files
 */
class FileUtil
{
    /**
     * @var array
     */
    public static $imageExtensions = ['JPG', 'PNG', 'GIF'];

    /**
     * Obtener el listado de archivos de una cuenta.
     *
     * @param int $accountId Con el Id de la cuenta
     * @return FileData[]|array Con los archivos de la cuenta.
     */
    public static function getAccountFiles($accountId)
    {
        $query = 'SELECT accfile_id,
            accfile_name,
            accfile_size,
            accfile_thumb,
            accfile_type
            FROM accFiles
            WHERE accfile_accountId = ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\FileData');
        $Data->setQuery($query);
        $Data->addParam($accountId);

        return DB::getResultsArray($Data);
    }

    /**
     * Obtener el número de archivo de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return int con el número de archivos
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function countAccountFiles($accountId)
    {
        $query = 'SELECT accfile_id FROM accFiles WHERE accfile_accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        DB::getQuery($Data);

        return $Data->getQueryNumRows();
    }

    /**
     * Elimina los archivos de una cuenta en la BBDD.
     *
     * @param int $accountId con el Id de la cuenta
     * @throws SPException
     */
    public static function deleteAccountFiles($accountId)
    {
        $query = 'DELETE FROM accFiles WHERE accfile_accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);
        $Data->setOnErrorMessage(__('Error al eliminar archivos asociados a la cuenta', false));

        DB::getQuery($Data);
    }

    /**
     * @param FileData $FileData
     * @return bool
     */
    public static function isImage(FileData $FileData)
    {
        return in_array(mb_strtoupper($FileData->getAccfileExtension()), FileUtil::$imageExtensions);
    }
}