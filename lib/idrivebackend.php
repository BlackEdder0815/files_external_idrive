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
		->setIdentifier('acd')
		->addIdentifierAlias('\OCA\Files_External\Storage\ACD') // legacy compat
		->setStorageClass(\OCA\Files_External_ACD\lib\ACD::class)
		->setText($l->t('ACD'))
		->addParameters([
				(new DefinitionParameter('email', $l->t('E-Mail'))),
				(new DefinitionParameter('auth-code', $l->t('Auth-Code'))),
				(new DefinitionParameter('passphrase', $l->t('(optional) Pass-Phrase for file name encryption')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
		])
		->addAuthScheme(AccessKey::SCHEME_NULL)
		->setLegacyAuthMechanism(new NullMechanism($l));
		
	}
	
	function getCustomJs() {
		\script('files_external_acd', 'acd');
		return [];
	}
}