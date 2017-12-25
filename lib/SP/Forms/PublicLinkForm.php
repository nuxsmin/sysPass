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

namespace SP\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\PublicLinkData;
use SP\Http\Request;
use SP\Mgmt\PublicLinks\PublicLink;

/**
 * Class PublicLinkForm
 *
 * @package SP\Forms
 */
class PublicLinkForm extends FormBase implements FormInterface
{
    /**
     * @var PublicLinkData
     */
    protected $PublicLinkData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::PUBLICLINK_CREATE:
            case ActionsInterface::PUBLICLINK_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return true;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->PublicLinkData = new PublicLinkData();
        $this->PublicLinkData->setPublicLinkId($this->itemId);
        $this->PublicLinkData->setTypeId(PublicLink::TYPE_ACCOUNT);
        $this->PublicLinkData->setItemId(Request::analyze('accountId', 0));
        $this->PublicLinkData->setNotify(Request::analyze('notify', false, false, true));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->PublicLinkData->getPublicLinkItemId()) {
            throw new ValidationException(__u('Es necesario una cuenta'));
        }
    }

    /**
     * @return mixed
     */
    public function getItemData()
    {
        return $this->PublicLinkData;
    }
}