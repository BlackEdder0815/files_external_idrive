<?php
/**
 * ownCloud - acdstorage
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrick Sona <dev@patson.de>
 * @copyright Patrick Sona 2016
 */

namespace OCA\IDriveStorage\AppInfo;

use OCP\AppFramework\App;

/**
 * Additional autoloader registration, e.g. registering composer autoloaders
 */
// require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../3rd-party/autoload.php';


require_once __DIR__ . '/Application.php';
require_once __DIR__ . '/../lib/idrive.php';
require_once __DIR__ . '/../lib/idrivebackend.php';
require_once __DIR__ . '/../lib/OwnCloudCache.php';
