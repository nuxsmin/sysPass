/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

/** FROM: https://stackoverflow.com/a/47016383 **/

/* SELECT CONCAT('TRUNCATE TABLE ', table_name, ';')
FROM information_schema.tables
WHERE table_type = 'BASE TABLE'
  AND table_schema = DATABASE(); */

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE Account;
TRUNCATE TABLE AccountFile;
TRUNCATE TABLE AccountHistory;
TRUNCATE TABLE AccountToFavorite;
TRUNCATE TABLE AccountToTag;
TRUNCATE TABLE AccountToUser;
TRUNCATE TABLE AccountToUserGroup;
TRUNCATE TABLE AuthToken;
TRUNCATE TABLE Category;
TRUNCATE TABLE Client;
TRUNCATE TABLE Config;
TRUNCATE TABLE CustomFieldData;
TRUNCATE TABLE CustomFieldDefinition;
TRUNCATE TABLE CustomFieldType;
TRUNCATE TABLE EventLog;
TRUNCATE TABLE ItemPreset;
TRUNCATE TABLE Notification;
TRUNCATE TABLE Plugin;
TRUNCATE TABLE PluginData;
TRUNCATE TABLE PublicLink;
TRUNCATE TABLE Tag;
TRUNCATE TABLE Track;
TRUNCATE TABLE User;
TRUNCATE TABLE UserGroup;
TRUNCATE TABLE UserPassRecover;
TRUNCATE TABLE UserProfile;
TRUNCATE TABLE UserToUserGroup;

SET FOREIGN_KEY_CHECKS = 1;