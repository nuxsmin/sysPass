<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace Theme;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Init;
use SP\Core\ThemeIconsBase;
use SP\Html\Assets\FontIcon;
use SP\Html\Assets\ImageIcon;

/**
 * Class Icons con los iconos del tema visual
 *
 * @package Theme
 */
class Icons extends ThemeIconsBase
{
    /**
     * Establecer los iconos utilizados en el DataGrid
     */
    public function setIcons()
    {
        $this->iconAdd = new ImageIcon(Init::$WEBURI . '/imgs/add.png', null, _('Añadir'));
        $this->iconView = new ImageIcon(Init::$WEBURI . '/imgs/view.png', null, _('Ver Detalles'));
        $this->iconViewPass = new ImageIcon(Init::$WEBURI . '/imgs/user-pass.png', null, _('Ver Clave'));
        $this->iconEdit = new ImageIcon(Init::$WEBURI . '/imgs/edit.png', null, _('Editar'));
        $this->iconDelete = new ImageIcon(Init::$WEBURI . '/imgs/delete.png', null, _('Eliminar'));
        $this->iconEditPass = new ImageIcon(Init::$WEBURI . '/imgs/key.png', null, _('Cambiar Clave'));
        $this->iconAppAdmin = new ImageIcon(Init::$WEBURI . '/imgs/check_blue.png', null, _('Admin Aplicación'));
        $this->iconAccAdmin = new ImageIcon(Init::$WEBURI . '/imgs/check_orange.png', null, _('Admin Cuentas'));
        $this->iconLdapUser = new ImageIcon(Init::$WEBURI . '/imgs/ldap.png', null, _('Usuario de LDAP'));
        $this->iconDisabled = new ImageIcon(Init::$WEBURI . '/imgs/disabled.png', null, _('Deshabilitado'));
        $this->iconRefresh = new ImageIcon(Init::$WEBURI . '/imgs/refresh.png', null, _('Actualizar'));
        $this->iconCopy = new ImageIcon(Init::$WEBURI . '/imgs/btn_copy.png', null, _('Copiar'));
        $this->iconClipboard = new ImageIcon(Init::$WEBURI . '/imgs/clipboard.png');
        $this->iconEmail = new ImageIcon(Init::$WEBURI . '/imgs/request.png', null, _('Email'));
        $this->iconOptional = new ImageIcon(Init::$WEBURI . '/imgs/action.png');
        $this->iconPublicLink = new ImageIcon(Init::$WEBURI . '/imgs/action.png');
        $this->iconBack = new ImageIcon(Init::$WEBURI . '/imgs/back.png', null, _('Volver'));
        $this->iconRestore = new ImageIcon(Init::$WEBURI . '/imgs/restore.png', null, ('Restaurar'));
        $this->iconSave = new ImageIcon(Init::$WEBURI . '/imgs/check.png', null, _('Guardar'));
        $this->iconHelp = new ImageIcon(Init::$WEBURI . '/imgs/help.png', null, _('Ayuda'));
        $this->iconClear = new ImageIcon(Init::$WEBURI . '/imgs/clear.png', null, _('Limpiar'));
        $this->iconPlay = new ImageIcon(Init::$WEBURI . '/imgs/start.png', null, _('Realizar'));
        $this->iconDownload = new ImageIcon(Init::$WEBURI . '/imgs/download.png', null, _('Descargar'));
        $this->iconWarning = new ImageIcon(Init::$WEBURI . '/imgs/warning.png', null, _('Aviso'));

        // Iconos de Navegación
        $this->iconNavPrev = new FontIcon('chevron_left', null, _('Página anterior'));
        $this->iconNavNext = new FontIcon('chevron_right', null, _('Página siguiente'));
        $this->iconNavFirst = new FontIcon('arrow_back', null, _('Primera página'));
        $this->iconNavLast = new FontIcon('arrow_forward', null, _('Última página'));

        // Iconos de Ordenación
        $this->iconUp = new FontIcon('arrow_drop_up');
        $this->iconDown = new FontIcon('arrow_drop_down');
    }
}