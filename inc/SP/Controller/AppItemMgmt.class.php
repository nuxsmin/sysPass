<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;
use SP\Core\Template;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\Http\Request;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomField;
use SP\Core\SessionUtil;
use SP\Mgmt\CustomFields\CustomFieldTypes;
use SP\Mgmt\Files\File;
use SP\DataModel\TagData;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Tags\Tag;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Clase encargada de preparar la presentación de las vistas de gestión de cuentas
 *
 * @package Controller
 */
class AppItemMgmt extends Controller implements ActionsInterface
{
    /**
     * @var int
     */
    private $_module = 0;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la ficha de cliente
     */
    public function getCustomer()
    {
        $this->_module = self::ACTION_MGM_CUSTOMERS;
        $this->view->addTemplate('customers');

        $this->view->assign('customer', ($this->view->itemId) ? Customer::getItem()->getById($this->view->itemId)->getItemData() : new CustomerData());
        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        $this->view->assign('customFields', CustomField::getItem(new CustomFieldData($this->_module))->getById($this->view->itemId));
    }

    /**
     * Obtener los datos para la ficha de categoría
     */
    public function getCategory()
    {
        $this->_module = self::ACTION_MGM_CATEGORIES;
        $this->view->addTemplate('categories');

        $this->view->assign('category', ($this->view->itemId) ? Category::getItem()->getById($this->view->itemId)->getItemData() : new CategoryData());
        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', FileUtil::getAccountFiles($this->view->accountId));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }

        $this->view->addTemplate('files');

        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la ficha de campo personalizado
     */
    public function getCustomField()
    {
        $this->view->addTemplate('customfields');

        $customField = ($this->view->itemId) ? CustomFieldDef::getItem()->getById($this->view->itemId)->getItemData() : new CustomFieldDefData();

        $this->view->assign('customField', $customField);
        $this->view->assign('field', $customField);
        $this->view->assign('types', CustomFieldTypes::getFieldsTypes());
        $this->view->assign('modules', CustomFieldTypes::getFieldsModules());
    }

    /**
     * Obtener los datos para la ficha de categoría
     */
    public function getTag()
    {
        $this->_module = self::ACTION_MGM_TAGS;
        $this->view->addTemplate('tags');

        $this->view->assign('tag', ($this->view->itemId) ? Tag::getItem()->getById($this->view->itemId)->getItemData() : new TagData());
    }
}
