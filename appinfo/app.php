<?php



require_once __DIR__ . '/autoload.php';

//$l = \OC::$server->getL10N('files_external_acd');

if (class_exists('\OCA\Files_External\AppInfo\Application')) {
	OC_App::loadApp('files_external');
	(new \OCA\Files_External_IDrive\AppInfo\Application())->register();
}

// $appContainer = \OC_Mount_Config::$app->getContainer();
// $backendService = $appContainer->query('OCA\Files_External\Service\BackendService');
// $backendProvider = new ACDBackendProvider($l);
// $backendService->registerBackendProvider($backendProvider);

// OC_Mount_Config::registerBackend('\OCA\Files_External_ACD\ACD', [
// 	'backend' => (string)$l->t('ACD'),
// 	'priority' => 100,
// 	'configuration' => [
// 		'host' => (string)$l->t('hostname'),
// 		'username' => (string)$l->t('Username'),
// 		'password' => (string)$l->t('Password'),
// 		'root' => '&' . $l->t('Remote subfolder'),
// 		'secure' => '!' . $l->t('Secure ftps://'),
// 		'port' => '&' . $l->t('Port'),
// 	],
// ]);
