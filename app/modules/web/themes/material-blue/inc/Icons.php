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

use SP\Modules\Web\UI\ThemeIcons;
use SP\Modules\Web\Html\Assets\FontIcon;

use function SP\__u;

$themeIcons = new ThemeIcons();
$themeIcons->addIcon('add', new FontIcon('add', 'sp-color-text--accent', __u('Add')));
$themeIcons->addIcon('view', new FontIcon('visibility', 'sp-color-text--accent', __u('View Details')));
$themeIcons->addIcon('viewPass', new FontIcon('lock_open', 'sp-color-text--accent', __u('View password')));
$themeIcons->addIcon('edit', new FontIcon('mode_edit', 'sp-color-text--warning', __u('Edit')));
$themeIcons->addIcon('delete', new FontIcon('remove_circle', 'sp-color-text--error', __u('Delete')));
$themeIcons->addIcon('editPass', new FontIcon('lock_outline', 'sp-color-text--warning', __u('Change Password')));
$themeIcons->addIcon('appAdmin', new FontIcon('star', 'sp-color-text--warning', __u('Application Admin')));
$themeIcons->addIcon('accAdmin', new FontIcon('star_half', 'sp-color-text--warning', __u('Accounts Admin')));
$themeIcons->addIcon('ldapUser', new FontIcon('business', 'sp-color-text--heritage', __u('LDAP User')));
$themeIcons->addIcon('disabled', new FontIcon('error', 'sp-color-text--error', __u('Disabled')));
$themeIcons->addIcon('enabled', new FontIcon('check_circle', 'sp-color-text--success', __u('Enabled')));
$themeIcons->addIcon('refresh', new FontIcon('refresh', 'sp-color-text--success', __u('Update')));
$themeIcons->addIcon('copy', new FontIcon('content_copy', 'sp-color-text--accent', __u('Copy')));
$themeIcons->addIcon('clipboard', new FontIcon('content_paste', 'sp-color-text--accent'));
$themeIcons->addIcon('email', new FontIcon('email', 'sp-color-text--accent', __u('Email')));
$themeIcons->addIcon('optional', new FontIcon('settings'));
$themeIcons->addIcon('publicLink', new FontIcon('link', 'sp-color-text--success'));
$themeIcons->addIcon('back', new FontIcon('arrow_back', 'sp-color-text--accent', __u('Back')));
$themeIcons->addIcon('restore', new FontIcon('restore', 'sp-color-text--success', __u('Restore')));
$themeIcons->addIcon('save', new FontIcon('save', 'sp-color-text--success', __u('Save')));
$themeIcons->addIcon('help', new FontIcon('help_outline', 'sp-color-text--accent', __u('Help')));
$themeIcons->addIcon('clear', new FontIcon('clear_all', 'sp-color--accent', __u('Clear')));
$themeIcons->addIcon('play', new FontIcon('play_circle_filled', 'sp-color-text--success', __u('Perform')));
$themeIcons->addIcon('download', new FontIcon('file_download', 'sp-color-text--accent', __u('Download')));
$themeIcons->addIcon('warning', new FontIcon('warning', 'sp-color-text--warning', __u('Warning')));
$themeIcons->addIcon('check', new FontIcon('cached', 'sp-color-text--accent', __u('Check')));
$themeIcons->addIcon('search', new FontIcon('search', 'sp-color-text--accent', __u('Search')));
$themeIcons->addIcon('account', new FontIcon('account_box', 'sp-color-text--accent'));
$themeIcons->addIcon('group', new FontIcon('group_work', 'sp-color-text--accent'));
$themeIcons->addIcon('settings', new FontIcon('settings', 'sp-color-text--accent', __u('Configuration')));
$themeIcons->addIcon('headline', new FontIcon('view_headline', 'sp-color-text--accent'));
$themeIcons->addIcon('info', new FontIcon('info_outline', 'sp-color-text--accent', __u('Information')));
$themeIcons->addIcon('critical', new FontIcon('error_outline', 'sp-color-text--error', __u('Critical')));
$themeIcons->addIcon('notices', new FontIcon('notifications', 'sp-color-text--accent', __u('Notifications')));
$themeIcons->addIcon('remove', new FontIcon('remove', 'sp-color-text--accent', __u('Delete')));
$themeIcons->addIcon('previous', new FontIcon('chevron_left', null, __u('Previous page')));
$themeIcons->addIcon('next', new FontIcon('chevron_right', null, __u('Next page')));
$themeIcons->addIcon('first', new FontIcon('first_page', null, __u('First page')));
$themeIcons->addIcon('last', new FontIcon('last_page', null, __u('Last page')));
$themeIcons->addIcon('up', new FontIcon('arrow_drop_up'));
$themeIcons->addIcon('down', new FontIcon('arrow_drop_down'));

return $themeIcons;
