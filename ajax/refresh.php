<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
* @author Lukas Reschke <lukas@statuscode.ch>
* @author Robin McCorkell <robin@mccorkell.me.uk>
*
* @copyright Copyright (c) 2016, ownCloud GmbH.
* @license AGPL-3.0
*
* This code is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License, version 3,
* as published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License, version 3,
* along with this program.  If not, see <http://www.gnu.org/licenses/>
*
*/
OCP\JSON::checkAppEnabled('files_external_acd');
// OCP\JSON::callCheck();

OCP\JSON::checkAdminUser();
// $application = new OCA\Files_External\AppInfo\Application();
// include __DIR__ . '/../appinfo/app.php';
// $backends = $application->getBackends();
$user = \OC::$server->getUserSession()->getUser();



try {
	OC_App::loadApp("files_external");
	OC_App::loadApp("files_external_acd");
	$mountConfigManager = \OC::$server->getMountProviderCollection();
	$mounts = $mountConfigManager->getMountsForUser($user);
	array_filter($mounts, function($var){
		if($var->getMountPoint() === "/root/files/ACD/") { // FIXME use parameter at this place
			return true;
		}
		return false;
	});
	foreach ($mounts as $acdMount) {
		// only one should be left
		$acdMount->getStorage()->isUpdatable('###resetcache###');
	}

} catch (\OC\HintException $e) {
	\OCP\JSON::error($e->getMessage());
}

\OCP\JSON::success();
