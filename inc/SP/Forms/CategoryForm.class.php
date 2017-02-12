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

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CategoryData;
use SP\Http\Request;

/**
 * Class CategoryForm
 *
 * @package SP\Forms
 */
class CategoryForm extends FormBase implements FormInterface
{
    /**
     * @var CategoryData
     */
    protected $CategoryData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACTION_MGM_CATEGORIES_NEW:
            case ActionsInterface::ACTION_MGM_CATEGORIES_EDIT:
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
        $this->CategoryData = new CategoryData();
        $this->CategoryData->setCategoryId($this->itemId);
        $this->CategoryData->setCategoryName(Request::analyze('name'));
        $this->CategoryData->setCategoryDescription(Request::analyze('description'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->CategoryData->getCategoryName()) {
            throw new ValidationException(__('Es necesario un nombre de categoría', false));
        }
    }

    /**
     * @return CategoryData
     */
    public function getItemData()
    {
        return $this->CategoryData;
    }
}