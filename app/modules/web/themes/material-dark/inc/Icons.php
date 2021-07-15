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

defined('APP_ROOT') || die();

use SP\Core\UI\ThemeIcons;
use SP\Html\Assets\FontIcon;

$themeIcons = new ThemeIcons();
$themeIcons->addIcon('add', new FontIcon('add', 'mdl-color-text--indigo-A200', __u('Add')));
$themeIcons->addIcon('view', new FontIcon('visibility', 'mdl-color-text--indigo-A200', __u('View Details')));
$themeIcons->addIcon('viewPass', new FontIcon('lock_open', 'mdl-color-text--indigo-A200', __u('View password')));
$themeIcons->addIcon('edit', new FontIcon('mode_edit', 'mdl-color-text--amber-A200', __u('Edit')));
$themeIcons->addIcon('delete', new FontIcon('remove_circle', 'mdl-color-text--red-A200', __u('Delete')));
$themeIcons->addIcon('editPass', new FontIcon('lock_outline', 'mdl-color-text--amber-A200', __u('Change Password')));
$themeIcons->addIcon('appAdmin', new FontIcon('star', 'mdl-color-text--amber-A100', __u('Application Admin')));
$themeIcons->addIcon('accAdmin', new FontIcon('star_half', 'mdl-color-text--amber-A100', __u('Accounts Admin')));
$themeIcons->addIcon('ldapUser', new FontIcon('business', 'mdl-color-text--deep-purple-A100', __u('LDAP User')));
$themeIcons->addIcon('disabled', new FontIcon('error', 'mdl-color-text--red-A100', __u('Disabled')));
$themeIcons->addIcon('enabled', new FontIcon('check_circle', 'mdl-color-text--teal-500', __u('Enabled')));
$themeIcons->addIcon('refresh', new FontIcon('refresh', 'mdl-color-text--teal-500', __u('Update')));
$themeIcons->addIcon('copy', new FontIcon('content_copy', 'mdl-color-text--indigo-A200', __u('Copy')));
$themeIcons->addIcon('clipboard', new FontIcon('content_paste', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('email', new FontIcon('email', 'mdl-color-text--indigo-A200', __u('Email')));
$themeIcons->addIcon('optional', new FontIcon('settings'));
$themeIcons->addIcon('publicLink', new FontIcon('link', 'mdl-color-text--teal-500'));
$themeIcons->addIcon('back', new FontIcon('arrow_back', 'mdl-color-text--indigo-A200', __u('Back')));
$themeIcons->addIcon('restore', new FontIcon('restore', 'mdl-color-text--teal-500', __u('Restore')));
$themeIcons->addIcon('save', new FontIcon('save', 'mdl-color-text--teal-500', __u('Save')));
$themeIcons->addIcon('help', new FontIcon('help_outline', 'mdl-color-text--primary', __u('Help')));
$themeIcons->addIcon('clear', new FontIcon('clear_all', 'mdl-color--indigo-A200', __u('Clear')));
$themeIcons->addIcon('play', new FontIcon('play_circle_filled', 'mdl-color-text--teal-500', __u('Perform')));
$themeIcons->addIcon('download', new FontIcon('file_download', 'mdl-color-text--indigo-A200', __u('Download')));
$themeIcons->addIcon('warning', new FontIcon('warning', 'mdl-color-text--amber-A100', __u('Warning')));
$themeIcons->addIcon('check', new FontIcon('cached', 'mdl-color-text--indigo-A200', __u('Check')));
$themeIcons->addIcon('search', new FontIcon('search', 'mdl-color-text--indigo-A200', __u('Search')));
$themeIcons->addIcon('account', new FontIcon('account_box', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('group', new FontIcon('group_work', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('settings', new FontIcon('settings', 'mdl-color-text--indigo-A200', __u('Configuration')));
$themeIcons->addIcon('headline', new FontIcon('view_headline', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('info', new FontIcon('info_outline', 'mdl-color-text--indigo-A200', __u('Information')));
$themeIcons->addIcon('critical', new FontIcon('error_outline', 'mdl-color-text--red-A200', __u('Critical')));
$themeIcons->addIcon('notices', new FontIcon('notifications', 'mdl-color-text--indigo-A200', __u('Notifications')));
$themeIcons->addIcon('remove', new FontIcon('remove', 'mdl-color-text--indigo-A200', __u('Delete')));
$themeIcons->addIcon('previous', new FontIcon('chevron_left', null, __u('Previous page')));
$themeIcons->addIcon('next', new FontIcon('chevron_right', null, __u('Next page')));
$themeIcons->addIcon('first', new FontIcon('first_page', null, __u('First page')));
$themeIcons->addIcon('last', new FontIcon('last_page', null, __u('Last page')));
$themeIcons->addIcon('up', new FontIcon('arrow_drop_up'));
$themeIcons->addIcon('down', new FontIcon('arrow_drop_down'));

return $themeIcons;