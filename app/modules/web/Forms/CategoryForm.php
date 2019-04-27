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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CategoryData;

/**
 * Class CategoryForm
 *
 * @package SP\Modules\Web\Forms
 */
final class CategoryForm extends FormBase implements FormInterface
{
    /**
     * @var CategoryData
     */
    protected $categoryData;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return CategoryForm
     * @throws ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::CATEGORY_CREATE:
            case ActionsInterface::CATEGORY_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->categoryData = new CategoryData();
        $this->categoryData->setId($this->itemId);
        $this->categoryData->setName($this->request->analyzeString('name'));
        $this->categoryData->setDescription($this->request->analyzeString('description'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->categoryData->getName()) {
            throw new ValidationException(__u('A category name needed'));
        }
    }

    /**
     * @return CategoryData
     */
    public function getItemData()
    {
        return $this->categoryData;
    }
}