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
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', \SP\Util::demoIsEnabled());
        $this->view->assign('sk', \SP\Common::getSessionKey());
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

        $this->view->assign('sk', \SP\Common::getSessionKey(true));

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
                    'onclick' => 'appMgmtData(this,' . self::ACTION_MGM_CATEGORIES_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_MGM_CATEGORIES_EDIT,
                    'title' => _('Editar Categoría'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_MGM_CATEGORIES_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png'
                ),
                'del' => array(
                    'id' => self::ACTION_MGM_CATEGORIES_DELETE,
                    'title' => _('Eliminar Categoría'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_MGM_CATEGORIES_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
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

        $this->view->assign('sk', \SP\Common::getSessionKey(true));

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
                    'onclick' => 'appMgmtData(this,' . self::ACTION_MGM_CUSTOMERS_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_MGM_CUSTOMERS_EDIT,
                    'title' => _('Editar Cliente'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_MGM_CUSTOMERS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png'
                ),
                'del' => array(
                    'id' => self::ACTION_MGM_CUSTOMERS_DELETE,
                    'title' => _('Eliminar Cliente'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_MGM_CUSTOMERS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
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
        $this->view->addTemplate('customers');

        $this->view->assign('customer', \SP\Customer::getCustomerData($this->view->itemId));
    }

    /**
     * Obtener los datos para la ficha de categoría
     */
    public function getCategory()
    {
        $this->view->addTemplate('categories');

        $this->view->assign('category', \SP\Category::getCategoryData($this->view->itemId));
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     */
    public function getFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', \SP\Common::parseParams('g', 'id', 0));
        $this->view->assign('deleteEnabled', \SP\Common::parseParams('g', 'del', 0));
        $this->view->assign('files', \SP\Files::getFileList($this->view->accountId, $this->view->deleteEnabled));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }

        $this->view->addTemplate('files');

        $this->view->assign('sk', \SP\Common::getSessionKey());
    }
}
