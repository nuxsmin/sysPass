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

use SP\ApiTokens;
use SP\CustomFieldDef;
use SP\CustomFields;
use SP\SessionUtil;

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
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', \SP\Util::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey());
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

        $categoriesTableProp = array(
            'tblId' => 'tblCategories',
            'header' => '',
            'tblHeaders' => array(_('Nombre'), _('Descripción')),
            'tblRowSrc' => array('category_name', 'category_description'),
            'tblRowSrcId' => 'category_id',
            'onCloseAction' => self::ACTION_MGM,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_MGM_CATEGORIES_NEW,
                    'title' => _('Nueva Categoría'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CATEGORIES_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'icon' => 'add',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_MGM_CATEGORIES_EDIT,
                    'title' => _('Editar Categoría'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CATEGORIES_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'del' => array(
                    'id' => self::ACTION_MGM_CATEGORIES_DELETE,
                    'title' => _('Eliminar Categoría'),
                    'onclick' => 'sysPassUtil.Common.appMgmtDelete(this,' . self::ACTION_MGM_CATEGORIES_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                )
            )
        );

        $categoriesTableProp['cellWidth'] = floor(65 / count($categoriesTableProp['tblHeaders']));

        $this->view->append(
            'tabs',
            array(
                'title' => _('Gestión de Categorías'),
                'query' => \SP\Category::getCategories(),
                'props' => $categoriesTableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5))
        );
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

        $customersTableProp = array(
            'tblId' => 'tblCustomers',
            'header' => '',
            'tblHeaders' => array(_('Nombre'), _('Descripción')),
            'tblRowSrc' => array('customer_name', 'customer_description'),
            'tblRowSrcId' => 'customer_id',
            'onCloseAction' => self::ACTION_MGM,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_MGM_CUSTOMERS_NEW,
                    'title' => _('Nuevo Cliente'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CUSTOMERS_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_MGM_CUSTOMERS_EDIT,
                    'title' => _('Editar Cliente'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CUSTOMERS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'del' => array(
                    'id' => self::ACTION_MGM_CUSTOMERS_DELETE,
                    'title' => _('Eliminar Cliente'),
                    'onclick' => 'sysPassUtil.Common.appMgmtDelete(this,' . self::ACTION_MGM_CUSTOMERS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                )
            )
        );

        $customersTableProp['cellWidth'] = floor(65 / count($customersTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Clientes'),
                'query' => \SP\Customer::getCustomers(),
                'props' => $customersTableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5))
        );
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->view->addTemplate('tabs-start');
        $this->view->addTemplate('mgmttabs');
        $this->view->addTemplate('tabs-end');

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

        $this->view->assign('customer', \SP\Customer::getCustomerData($this->view->itemId));
        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la ficha de categoría
     */
    public function getCategory()
    {
        $this->_module = self::ACTION_MGM_CATEGORIES;
        $this->view->addTemplate('categories');

        $this->view->assign('category', \SP\Category::getCategoryData($this->view->itemId));
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
    public function getFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', \SP\Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', \SP\Request::analyze('del', 0));
        $this->view->assign('files', \SP\Files::getFileList($this->view->accountId));

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

        $tableProp = array(
            'tblId' => 'tblCustomFields',
            'header' => '',
            'tblHeaders' => array(_('Módulo'), _('Nombre'), _('Tipo')),
            'tblRowSrc' => array('module', 'name', 'typeName'),
            'tblRowSrcId' => 'id',
            'onCloseAction' => self::ACTION_MGM,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_MGM_CUSTOMFIELDS_NEW,
                    'title' => _('Nuevo Campo'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CUSTOMFIELDS_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_MGM_CUSTOMFIELDS_EDIT,
                    'title' => _('Editar Campo'),
                    'onclick' => 'sysPassUtil.Common.appMgmtData(this,' . self::ACTION_MGM_CUSTOMFIELDS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'del' => array(
                    'id' => self::ACTION_MGM_CUSTOMFIELDS_DELETE,
                    'title' => _('Eliminar Campo'),
                    'onclick' => 'sysPassUtil.Common.appMgmtDelete(this,' . self::ACTION_MGM_CUSTOMFIELDS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                )
            )
        );

        $tableProp['cellWidth'] = floor(65 / count($tableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Campos Personalizados'),
                'query' => \SP\CustomFieldDef::getCustomFields(),
                'props' => $tableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5))
        );
    }

    /**
     * Obtener los datos para la ficha de campo personalizado
     */
    public function getCustomField()
    {
        $this->view->addTemplate('customfields');

        $customField = \SP\CustomFieldDef::getCustomFields($this->view->itemId, true);
        $field = unserialize($customField->customfielddef_field);

        $this->view->assign('gotData', ($customField && $field instanceof CustomFieldDef));
        $this->view->assign('customField', $customField);
        $this->view->assign('field', $field);
        $this->view->assign('types', \SP\CustomFieldDef::getFieldsTypes());
        $this->view->assign('modules', \SP\CustomFieldDef::getFieldsModules());
    }
}
