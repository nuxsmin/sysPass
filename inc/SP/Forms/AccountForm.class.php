<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Forms;

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\AccountData;
use SP\Http\Request;

/**
 * Class AccountForm
 *
 * @package SP\Account
 */
class AccountForm
{
    /**
     * @var AccountData
     */
    protected $AccountData;

    /**
     * AccountForm constructor.
     *
     * @param $AccountData
     */
    public function __construct($AccountData)
    {
        $this->AccountData = $AccountData;
    }

    /**
     * Validar el formulario
     *
     * @param $action
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                if (!$this->AccountData->getAccountPass()) {
                    throw new ValidationException(_('Es necesaria una clave'));
                } elseif (Request::analyzeEncrypted('passR') !== $this->AccountData->getAccountPass()){
                    throw new ValidationException(_('Las claves no coinciden'));
                }
                break;
            case ActionsInterface::ACTION_ACC_EDIT:
                if (!$this->AccountData->getAccountName()) {
                    throw new ValidationException(_('Es necesario un nombre de cuenta'));
                } elseif (!$this->AccountData->getAccountCustomerId()) {
                    throw new ValidationException(_('Es necesario un nombre de cliente'));
                } elseif (!$this->AccountData->getAccountLogin()) {
                    throw new ValidationException(_('Es necesario un usuario'));
                } elseif (!$this->AccountData->getAccountCategoryId()) {
                    throw new ValidationException(_('Es necesario una categoría'));
                }
                break;
            case ActionsInterface::ACTION_ACC_NEW:
                if (!$this->AccountData->getAccountName()) {
                    throw new ValidationException(_('Es necesario un nombre de cuenta'));
                } elseif (!$this->AccountData->getAccountCustomerId()) {
                    throw new ValidationException(_('Es necesario un nombre de cliente'));
                } elseif (!$this->AccountData->getAccountLogin()) {
                    throw new ValidationException(_('Es necesario un usuario'));
                } elseif (!$this->AccountData->getAccountPass()) {
                    throw new ValidationException(_('Es necesaria una clave'));
                } elseif (Request::analyzeEncrypted('passR') !== $this->AccountData->getAccountPass()){
                    throw new ValidationException(_('Las claves no coinciden'));
                }elseif (!$this->AccountData->getAccountCategoryId()) {
                    throw new ValidationException(_('Es necesario una categoría'));
                }
                break;
            default:
                if (!$this->AccountData->getAccountId()) {
                    throw new ValidationException(_('Id inválido'));
                }
        }
    }
}