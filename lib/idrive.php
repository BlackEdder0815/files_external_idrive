<?php
/**
 * ownCloud - acdstorage
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrick Sona <dev@patson.de>
 * @copyright Patrick Sona 2016
 * 
 * see https://github.com/owncloud/documentation/issues/1112 for some help related to the used methods
 */

namespace OCA\Files_External_IDrive\lib;

use CloudDrive\CloudDrive;
use CloudDrive\Cache\OwnCloud;
use CloudDrive\Node;
use Icewind\Streams\IteratorDirectory;
use CloudDrive\Cryptor;

$ACDinitalized = false;

class IDrive extends \OC\Files\Storage\Common {

	private $clouddrive;
	
	/**
	 * @var array
	 */
	private static $tmpFiles = [];
	private static $fileCache = [];
	private static $writeBackCach = [];
	private $id;
	
	public function __construct($parameters) {
		global $ACDinitialized;
		if (isset($parameters['email']) && isset($parameters['auth-code'])) 
		{
			// 		$datadir = __DIR__ . "/../../../data/files_external_acd";
			// 		$cache = new SQLite("blackedder@gmx.de", $datadir);
			// 		$this->clouddrive = new CloudDrive("blackedder@gmx.de", null, null, $cache);
			$email = $parameters['email'];
			$cache = new OwnCloudCache("acd"); //FIXME own instance-ID per mount
			$secret = $parameters['passphrase'];
			if(!empty($secret)) {
				Cryptor::initialize($secret);
			}
			
			$this->clouddrive = new CloudDrive($email, null, null, $cache);
			if(!$cache->loadAccountConfig($email)) {
				// initialize cache
				$tokenarray = json_decode($parameters['auth-code']);
				if(!$tokenarray) {
					throw new \Exception('Format of token invalid. Must be json encoded!');
				}
				$response = $this->clouddrive->getAccount()->setResponse($tokenarray->access_token, $tokenarray->refresh_token, $tokenarray->token_type, $tokenarray->expires_in);
				if(!$response['success']) {
					throw new \Exception($response["data"]["message"]);
				}
				$retval = $this->clouddrive->getAccount()->renewAuthorization();
				if(!$retval["success"]) {
					throw new \Exception($retval["data"]["message"]);
				}
			}
			
			$response = $this->clouddrive->getAccount()->authorize(); //FIXME should moved to functions, which realy contact amazon
			if(!$ACDinitialized) {

				Node::init($this->clouddrive->getAccount(), $cache);
				try{
					$result = Node::loadByPath("/");
				} catch(\Exception $e) {
					// no node found -> reinitialize first
					// $response = $this->clouddrive->getAccount()->authorize();
					$this->clouddrive->getAccount()->sync();
				}
				$ACDinitialized = TRUE;
			}
			$this->id = 'acd::' . $email; // FIXME maybe not really unique?!
		} else {
			throw new \Exception('Creating Amazon Cloud storage failed');
		}
		
	}
	
// 	public function hasUpdated($path, $time) {
// 		$stat = self::stat($path);
// 		return $stat['mtime'] > $time;
// 	}
	
	
	
	public function mkdir($path) {
		// authorize before creating dir
		//$response = $this->clouddrive->getAccount()->authorize();
		
		$response = $this->clouddrive->createDirectoryPath($path);
		return $response["success"];
	}
	
	public function rmdir($path) {
		return $this->unlink($path);
	}
	
	public function opendir($path) {
		$node = Node::loadByPath($path);
		$childs = $node->getChildren();
		if (is_null($childs) === false) {
			$files = [];
			foreach ($childs as $file) {
				if(!$file->inTrash()) {
					$files[] = basename($file->getPath());
				}
			}
			return IteratorDirectory::wrap($files);
		}
		return false;
	}
	
	public function stat($path) {
		$node = Node::loadByPath($path);
		if(is_null($node) === false) {
			$data = [];
			$metadata = $node->flatten();
			$data["mtime"] = strtotime($metadata["modifiedDate"]);
			$data["size"] = 0;
				
			try {
				if($node->isFolder()) {
//					$childs = $node->getChildren();
//					foreach ($childs as $child) {
//						$stat = self::stat($child->getPath());
//						if($data["mtime"] < $stat["mtime"]) {
//							$data["mtime"] = $stat["mtime"];
//						}
//						$data["size"] = $data["size"] + $stat["size"];
//					}
				} else {
					$data["size"] = $metadata["contentProperties.size"];
				}
			} catch(\Exception $e) {
				// do not block the whole drive, if a single error is inside the tree
				\OCP\Util::logException('files_external_acd', $e);
				$data["size"] = 0;
			}
			
			return $data;
		
		}
		return false;
	}
	
	public function filetype($path) {
		$node = Node::loadByPath($path);
		if(is_null($node) === false) {
			if($node->isFile()) {
				return "file";
			} elseif($node->isFolder()) {
				return "dir";
			} elseif($node->isRoot()) {
				return "dir";
			}
		}
		return false;
	}
	
	public function file_exists($path) {
		$node = Node::loadByPath($path);
		
		if(is_null($node)) {
			return false;
		}
		return true;
	}

	public function unlink($path) {
		$node = Node::loadByPath($path);
		if(is_null($node)) {
			return false;
		}
		// authorize before delete
		//$response = $this->clouddrive->getAccount()->authorize();
		
		$response =$node->trash();
		if($response["success"] !== true) {
			return false;
		}
		return true;
	}
	
	public function fopen($path, $mode) {
		
		switch ($mode) {
			case 'r':
			case 'rb':

				// check, if file is in short-time-cache -- prevent double downloads
				if(isset(self::$fileCache[$path])) {
					return fopen(self::$fileCache[$path], 'r');
				}
				
				$tmpFile = \OC::$server->getTempManager()->getTempBaseDir() . "/" . uniqid();
				
				self::$tmpFiles[$tmpFile] = $path;
		
				$node = Node::loadByPath($path);
				$response = $node->download($tmpFile);
				if($response["success"] !== true) {
					return false;
				}
				self::$fileCache[$path] = $tmpFile;
				return fopen($tmpFile, 'r');
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, [$this, 'writeBack']);
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tmpFiles[$tmpFile] = $path;
		
				return fopen('close://' . $tmpFile, $mode);
		}
		return false;
	}
	
	public function writeBack($tmpFile) {
		if (!isset(self::$tmpFiles[$tmpFile])) {
			return false;
		}
		
		self::$writeBackCach[self::$tmpFiles[$tmpFile]] = $tmpFile;
		//FIXME if part-renaming can be switched off by storageeeeee, use this code
// 		$response = $this->clouddrive->uploadFile2($tmpFile, self::$tmpFiles[$tmpFile], true);
// 		if($response["success"] !== true) {
// 			return false;
// 		}
// 		unlink($tmpFile);
		return true;
	}
	
	
	public function rename($path1, $path2) {
		//authorize before rename/upload
		//$response = $this->clouddrive->getAccount()->authorize();
		
		// to increase performance, we are writing the file AFTER renaming (for upload)
		// FIXME this should be removed, if storage can be switched to use no part-files
		if(isset(self::$writeBackCach[$path1])) {
				$tmpfile = self::$writeBackCach[$path1];
				
				
				$response = $this->clouddrive->uploadFile2($tmpfile, $path2, true, true);
				if($response["success"] !== true) {
					throw new \Exception($response["data"]['message']);
					return false;
				}
				unlink($tmpfile);
			return true;
		}
		
		
		if($path1 === $path2) {
			// nothing todo
			return true;
		}
		$node = Node::loadByPath($path1);
		if(is_null($node)) {
			return false;
		}
		
		$sourcearray = explode("/", $path1);
		$sourcepath = [];
		$sourcename;
		if(sizeof($sourcearray) === 1) {
			$sourcename = $sourcearray[0];
		} else {
			$sourcearrayChunk = array_chunk($sourcearray, sizeof($sourcearray) - 1);
			$sourcepath = $sourcearrayChunk[0];
			$sourcename = $sourcearrayChunk[1][0];
		}

		$destarray = explode("/", $path2);
		$destpath = [];
		$destname;
		if(sizeof($destarray) === 1) {
			$destname = $destarray[0];
		} else {
			$destarrayChunk = array_chunk($destarray, sizeof($destarray) - 1);
			$destpath = $destarrayChunk[0];
			$destname = $destarrayChunk[1][0];
		}
		
		if($sourcepath !== $destpath) {
			// move to another dir
			$destnode = Node::loadByPath(implode("/", $destpath));
			if(is_null($destnode)) {
				return false;
			}
			$result = $node->move($destnode);
			if($result["success"] !== true) {
				return false;
			}
		}
		
		if($sourcename !== $destname) {
			// rename file
			$result = $node->rename($destname);
			if($result["success"] !== true) {
				return false;
			}
		}
		return true;
	}
	
	public function isReadable($path) {
		return true;
	}
	
	public function isUpdatable($path) {
		if($path === '###resetcache###') {
			$this->clouddrive->getAccount()->clearCache();
			
			// authorize before upload
			// $response = $this->clouddrive->getAccount()->authorize();
				
			$this->clouddrive->getAccount()->sync();
			return true;
		} 
		return true;
	}

	public function isCreatable($path) {
		return true;
	}
	
	public function isDeletable($path) {
		return true;
	}
	
	public function isSharable($path) {
		return true;
	}
	
	public function free_space($path) {
		return \OCP\Files\FileInfo::SPACE_UNKNOWN;
	}
	
	public function touch($path, $mtime = null) {
		return true;
	}
	
	public function getId() {
		return $this->id;
	}
}