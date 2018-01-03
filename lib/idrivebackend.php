<?php
namespace OCA\Files_External_IDrive\lib;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Auth\AmazonS3\AccessKey;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCP\IL10N;
use OCA\Files_External\Lib\DefinitionParameter;

require_once __DIR__ . '/../../../lib/private/legacy/template/functions.php';

class IDriveBackend extends Backend{
	public function __construct(IL10N $l) {
		$this
		->setIdentifier('idrive')
		->addIdentifierAlias('\OCA\Files_External\Storage\IDrive') // legacy compat
		->setStorageClass(\OCA\Files_External_IDrive\lib\IDrive::class)
		->setText($l->t('iDrive'))
		->addParameters([
				(new DefinitionParameter('username', $l->t('Username'))),
				(new DefinitionParameter('Password', $l->t('Password'))),
				(new DefinitionParameter('passphrase', $l->t('(optional) Pass-Phrase for file name encryption')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
		])
		->addAuthScheme(AccessKey::SCHEME_NULL)
		->setLegacyAuthMechanism(new NullMechanism($l));
		
	}
	
	function getCustomJs() {
		\script('files_external_idrive', 'idrive');
		return [];
	}
}