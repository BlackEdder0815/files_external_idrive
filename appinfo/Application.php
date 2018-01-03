<?php

namespace OCA\Files_External_IDrive\AppInfo;


use OCP\AppFramework\App;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External_IDrive\lib\IDriveBackend;

class Application extends App implements IBackendProvider {
	
	
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_external_idrive', $urlParams);
	}
	
	public function register() {
		$server = $this->getContainer()->getServer();
		$backendService = $server->query('OCA\\Files_External\\Service\\BackendService');
		$backendService->registerBackendProvider($this);
	}
	
	function getBackends() {
		$container = $this->getContainer();
		
		return [
			$container->query(IDriveBackend::class)	
		];
// 		$backends = [];
// 		$int = 1;
// 		$backends["acdBackend"] = new ACDBackend($this->l);
		
// 		return $backends;
	}
	
}
