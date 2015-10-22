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

use SP\Core\ActionsInterface;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridIcon;
use SP\Html\DataGrid\DataGridTab;
use SP\Http\Request;
use SP\Mgmt\Category;
use SP\Mgmt\Customer;
use SP\Mgmt\CustomFieldDef;
use SP\Mgmt\CustomFields;
use SP\Core\SessionUtil;
use SP\Mgmt\Files;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las vistas de gestión de cuentas
 *
 * @package Controller
 */
class AccountsMgmtC extends Controller implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var int
     */
    private $_module = 0;
    /**
     * @var DataGridIcon
     */
    private $_iconAdd;
    /**
     * @var DataGridIcon
     */
    private $_iconView;
    /**
     * @var DataGridIcon
     */
    private $_iconEdit;
    /**
     * @var DataGridIcon
     */
    private $_iconDelete;


    /**
     * Constructor
     *
     * @param $template \SP\Core\Template con instancia de plantilla
     */
    public function __construct(\SP\Core\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey());

        $this->setIcons();
    }

    /**
     * Obtener los datos para la pestaña de categorías
     */
    public function getCategories()
    {
        $this->setAction(self::ACTION_MGM_CATEGORIES);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setName(_('Nueva Categoría'));
        $GridActionNew->setIcon($this->_iconAdd);
        $GridActionNew->setSkip(true);
        $GridActionNew->setIsNew(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setName(_('Editar Categoría'));
        $GridActionEdit->setIcon($this->_iconEdit);
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setName(_('Eliminar Categoría'));
        $GridActionDel->setIcon($this->_iconDelete);
        $GridActionDel->setIsDelete(true);
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('category_id');
        $GridData->addDataRowSource('category_name');
        $GridData->addDataRowSource('category_description');
        $GridData->setData(Category::getCategories());

        $Grid = new DataGridTab();
        $Grid->setId('tblCategories');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Categorías'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de clientes
     */
    public function getCustomers()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMERS);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setName(_('Nuevo Cliente'));
        $GridActionNew->setIcon($this->_iconAdd);
        $GridActionNew->setSkip(true);
        $GridActionNew->setIsNew(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setName(_('Editar Cliente'));
        $GridActionEdit->setIcon($this->_iconEdit);
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setName(_('Eliminar Cliente'));
        $GridActionDel->setIcon($this->_iconDelete);
        $GridActionDel->setIsDelete(true);
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('customer_id');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('customer_description');
        $GridData->setData(Customer::getCustomers());

        $Grid = new DataGridTab();
        $Grid->setId('tblCustomers');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Clientes'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->view->addTemplate('datatabs-grid');

        $this->view->assign('tabs', array());
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la ficha de cliente
     */
    public function getCustomer()
    {
        $this->_module = self::ACTION_MGM_CUSTOMERS;
        $this->view->addTemplate('customers');

        $this->view->assign('customer', Customer::getCustomerData($this->view->itemId));
        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la ficha de categoría
     */
    public function getCategory()
    {
        $this->_module = self::ACTION_MGM_CATEGORIES;
        $this->view->addTemplate('categories');

        $this->view->assign('category', Category::getCategoryData($this->view->itemId));
        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        // Se comprueba que hayan campos con valores para el elemento actual
        if (!$this->view->isView && CustomFields::checkCustomFieldExists($this->_module, $this->view->itemId)) {
            $this->view->assign('customFields', CustomFields::getCustomFieldsData($this->_module, $this->view->itemId));
        } else {
            $this->view->assign('customFields', CustomFields::getCustomFieldsForModule($this->_module));
        }
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', Files::getAccountFileList($this->view->accountId));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }

        $this->view->addTemplate('files');

        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la pestaña de campos personalizados
     */
    public function getCustomFields()
    {
        $this->setAction(self::ACTION_MGM_CUSTOMFIELDS);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setName(_('Nuevo Campo'));
        $GridActionNew->setIcon($this->_iconAdd);
        $GridActionNew->setSkip(true);
        $GridActionNew->setIsNew(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setName(_('Editar Campo'));
        $GridActionEdit->setIcon($this->_iconEdit);
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setName(_('Eliminar Campo'));
        $GridActionDel->setIcon($this->_iconDelete);
        $GridActionDel->setIsDelete(true);
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Módulo'));
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Tipo'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('module');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('typeName');
        $GridData->setData(CustomFieldDef::getCustomFields());

        $Grid = new DataGridTab();
        $Grid->setId('tblCustomFields');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(_('Campos Personalizados'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la ficha de campo personalizado
     */
    public function getCustomField()
    {
        $this->view->addTemplate('customfields');

        $customField = CustomFieldDef::getCustomFields($this->view->itemId, true);
        $field = (is_object($customField)) ? unserialize($customField->customfielddef_field) : null;

        if (is_object($field) && get_class($field) === '__PHP_Incomplete_Class') {
            $field = Util::castToClass('SP\Mgmt\CustomFieldDef', $field);
        }

        $this->view->assign('gotData', ($customField && $field instanceof CustomFieldDef));
        $this->view->assign('customField', $customField);
        $this->view->assign('field', $field);
        $this->view->assign('types', CustomFieldDef::getFieldsTypes());
        $this->view->assign('modules', CustomFieldDef::getFieldsModules());
    }

    /**
     * Establecer los iconos utilizados en el DataGrid
     */
    private function setIcons()
    {
        $this->_iconAdd = new DataGridIcon('add', 'imgs/new.png', 'fg-blue80');
        $this->_iconView = new DataGridIcon('visibility', 'imgs/view.png', 'fg-blue80');
        $this->_iconEdit = new DataGridIcon('mode_edit', 'imgs/edit.png', 'fg-orange80');
        $this->_iconDelete = new DataGridIcon('delete', 'imgs/delete.png', 'fg-red80');
    }

    /**
     * Obtener los datos para la pestaña de categorías
     */
    public function getFiles()
    {
        $this->setAction(self::ACTION_MGM_FILES_VIEW);

        // FIXME: añadir perfil
        if (!$this->checkAccess()) {
            return;
        }

        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setName(_('Ver Archivo'));
        $GridActionView->setIcon($this->_iconView);
        $GridActionView->setOnClickFunction('sysPassUtil.Common.viewFile');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setName(_('Eliminar Archivo'));
        $GridActionDel->setIcon($this->_iconDelete);
        $GridActionDel->setIsDelete(true);
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Cuenta'));
        $GridHeaders->addHeader(_('Cliente'));
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Tipo'));
        $GridHeaders->addHeader(_('Tamaño'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('accfile_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('accfile_name');
        $GridData->addDataRowSource('accfile_type');
        $GridData->addDataRowSource('accfile_size');
        $GridData->setData(Files::getFileList());

        $Grid = new DataGridTab();
        $Grid->setId('tblFiles');
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Archivos'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }
}
