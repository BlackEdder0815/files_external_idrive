<?php
namespace OCA\Files_External_IDrive\AppInfo;


/** @var $this \OC\Route\Router */

$this->create('files_external_acd_refresh', 'refresh.php')
->actionInclude('files_external_acd/ajax/refresh.php');
