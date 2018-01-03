<?php
/**
 * @author Alex Phillips <ahp118@gmail.com>
 * Date: 7/23/15
 * Time: 5:19 PM
 */

namespace OCA\Files_External_ACD\lib;

use CloudDrive\Account;
use CloudDrive\Cache;
use CloudDrive\Node;
use JakubOnderka\PhpParallelLint\Result;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * The SQL abstract class is what all SQL database cache classes will inherit
 * from.
 *
 * @package CloudDrive\Cache
 */
class OwnCloudCache implements Cache
{
	
	private $tableConfig;
	private $tableNodes;
	private $tableNodesNodes;
	
	public function __construct()
	{
		$this->tableConfig = "*PREFIX*files_external_acd_configs";
		$this->tableNodes = "*PREFIX*files_external_acd_nodes";
		$this->tableNodesNodes = "*PREFIX*files_external_acd_nodes_nodes";
	}
	
    /**
     * {@inheritdoc}
     */
    public function deleteAllNodes()
    {
    	$connection = \OC::$server->getDatabaseConnection();
    	$query = $connection->getQueryBuilder();
    	 try {
        	$connection->beginTransaction();	 
        	$query = $connection->getQueryBuilder();
        	$query->delete($this->tableNodes)->execute();
        	$query = $connection->getQueryBuilder();
        	$query->delete($this->tableNodesNodes)->execute();
        	$connection->commit();
        	
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteNodeById($id)
    {

    	$connection = \OC::$server->getDatabaseConnection();
    	$query = $connection->getQueryBuilder();
    	 
    	$query->selectDistinct('id')->from($this->tableNodes)->where($query->expr()->eq('id', $query->createNamedParameter($id)));
    	$result = $query->execute();
    	$row = $result->fetch();
    	$result->closeCursor();
    	
        if ($row) {
            try {
            	$connection->beginTransaction();
            	$query = $connection->getQueryBuilder();
            	$query->delete($this->tableNodes)->where($query->expr()->eq('id', $query->createNamedParameter($id)));
            	$query->execute();
            	$query = $connection->getQueryBuilder();
            	$query->delete($this->tableNodes)->where($query->expr()->eq('id_node', $query->createNamedParameter($id)));
            	$query->execute();
            	$connection->commit();

                return true;
            } catch (\Exception $e) {
            	$connection->rollBack();
            	throw $e;
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filterNodes(array $filters)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	
    	$query->select('raw_data', 'id')->from($this->tableNodes);
    	$first = true;
    	array_walk($fitlers, function($value,$key){
    		if($first) {
    			$first = false;
    			$query->where($query->expr()->eq($key, $query->createNamedParameter($value)));
    		} else {
    			$query->andWhere($query->expr()->eq($key, $query->createNamedParameter($value)));
    		}
    	});
    	
   		$result = $query->execute();
   		
   		$rows = $result->fetchAll();
   		
   		$result->closeCursor();
   		
   		$nodes = [];
   		foreach ($rows as $row) {
   			$nodes[$row['id']] = new Node(
                json_decode($row['raw_data'], true));
   		}

        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function findNodeById($id)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->selectDistinct('raw_data')->from($this->tableNodes)->where($query->expr()->eq('id', $query->createNamedParameter($id)));
    	$result = $query->execute();
    	$row = $result->fetch();
    	$result->closeCursor();
    	if($row) {
    		return new Node(
    				json_decode($row['raw_data'], true)
    				);
    	}
    	
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findNodesByMd5($md5)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->select('raw_data', 'id')->from($this->tableNodes)->where($query->expr()->eq('md5', $query->createNamedParameter($md5)));
    	$result = $query->execute();
    	$rows = $result->fetchAll();
    	$result->closeCursor();
    	$nodes = [];
    	foreach ($rows as $row) {
    		$nodes[$row['id']] = new Node(
    				json_decode($row['raw_data'], true));
    	}
    	
    	return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function findNodesByName($name)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->select('raw_data', 'id')->from($this->tableNodes)->where($query->expr()->eq('name', $query->createNamedParameter($name)));

    	$result = $query->execute();
    	$rows = $result->fetchAll();
    	$result->closeCursor();
    	$nodes = [];
    	foreach ($rows as $row) {
    		$nodes[$row['id']] = new Node(
    				json_decode($row['raw_data'], true));
    	}
        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeChildren(Node $node)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->select('n.raw_data', 'n.id')->from($this->tableNodes, 'n')
    		->join(
    				'n', 
    				$this->tableNodesNodes, 
    				'nn', 
    				$query->expr()->eq('n.id', 'nn.id_node'))
    		->where($query->expr()->eq('nn.id_parent', $query->createNamedParameter($node['id'])));
    	$result = $query->execute();
    	$rows = $result->fetchAll();
    	$result->closeCursor();
    	$nodes = [];
    	foreach ($rows as $row) {
    		$nodes[$row['id']] = new Node(
    				json_decode($row['raw_data'], true));
    	}
    	return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function loadAccountConfig($email)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->select('*')->from($this->tableConfig)->where($query->expr()->eq('email', $query->createNamedParameter($email)));
    	$result = $query->execute();
    	$row = $result->fetch();
    	$result->closeCursor();
    	 
    	return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function saveAccountConfig(Account $account)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$config = self::loadAccountConfig($account->getEmail());

        if (!$config) {
        	$query->insert($this->tableConfig)->values(array (
        			'email'           => $query->createNamedParameter($account->getEmail()),
        			'token_type'      => $query->createNamedParameter($account->getToken()['token_type']),
        			'expires_in'      => $query->createNamedParameter($account->getToken()['expires_in']),
        			'refresh_token'   => $query->createNamedParameter($account->getToken()['refresh_token']),
        			'access_token'    => $query->createNamedParameter($account->getToken()['access_token']),
        			'last_authorized' => $query->createNamedParameter($account->getToken()['last_authorized']),
        			'content_url'     => $query->createNamedParameter($account->getContentUrl()),
        			'metadata_url'    => $query->createNamedParameter($account->getMetadataUrl()),
        			'checkpoint'      => $query->createNamedParameter($account->getCheckpoint()),
        	));
        } else {
        	$query->update($this->tableConfig)
        	->set('email'          , $query->createNamedParameter($account->getEmail()))
        	->set('token_type'     , $query->createNamedParameter($account->getToken()['token_type']))
        	->set('expires_in'     , $query->createNamedParameter($account->getToken()['expires_in']))
        	->set('refresh_token'  , $query->createNamedParameter($account->getToken()['refresh_token']))
        	->set('access_token'   , $query->createNamedParameter($account->getToken()['access_token']))
        	->set('last_authorized', $query->createNamedParameter($account->getToken()['last_authorized']))
        	->set('content_url'    , $query->createNamedParameter($account->getContentUrl()))
        	->set('metadata_url'   , $query->createNamedParameter($account->getMetadataUrl()))
        	->set('checkpoint'     , $query->createNamedParameter($account->getCheckpoint()))
        	->where($query->expr()->eq('email', $query->createNamedParameter($account->getEmail())));
        }
		
        $query->execute();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function saveNode(Node $node)
    {
        if (!$node['name'] && $node['isRoot'] === true) {
            $node['name'] = 'Cloud Drive';
        }

        $n = self::findNodeById($node['id']);
        
        $connection = \OC::$server->getDatabaseConnection();
        $connection->beginTransaction();
        
        try {
	        if(!$n) {
	        	$query = $connection->getQueryBuilder();
	        	$query->insert($this->tableNodes)->values(array (
	        			'id'           => $query->createNamedParameter($node['id'])
	        			));
	        	$query->execute();
	        			 
	        }
	        
	        $query = $connection->getQueryBuilder();
	        $query->update($this->tableNodes)
	        ->set('name'     , $query->createNamedParameter($node['name']))
	        ->set('cryptname', $query->createNamedParameter($node['cryptname']))
	        ->set('kind'     , $query->createNamedParameter($node['kind']))
	        ->set('md5'      , $query->createNamedParameter($node['contentProperties']['md5']))
	        ->set('status'   , $query->createNamedParameter($node['status']))
	        ->set('created'  , $query->createNamedParameter($node['createdDate']))
	        ->set('modified' , $query->createNamedParameter($node['modifiedDate']))
	        ->set('raw_data' , $query->createNamedParameter(json_encode($node)))
	        ->where($query->expr()->eq('id', $query->createNamedParameter($node['id'])));
	        $query->execute();

	        $query = $connection->getQueryBuilder();
	         
            $parentIds = $node['parents'];
            
            $query->select('id', 'id_parent')->from($this->tableNodesNodes)->where($query->expr()->eq('id_node', $query->createNamedParameter($node['id'])));
            
            $result = $query->execute();
            $previousParents = $result->fetchAll();
            $result->closeCursor();

            foreach ($previousParents as $parent) {
            	if ($index = array_search($parent['id_parent'], $parentIds)) {
            		unset($parentIds[$index]);
            		continue;
            	} else {
            		$query = $connection->getQueryBuilder();
            		$query->delete($this->tableNodesNodes)->where($query->expr()->eq('id', $query->createNamedParameter($parent['id'])));
            		$query->execute();
            	}
            }
            

            foreach ($parentIds as $parentId) {
            	$query = $connection->getQueryBuilder();
            	$query->insert($this->tableNodesNodes)->values(array (
            			'id_node'           => $query->createNamedParameter($node['id']),
            			'id_parent'      => $query->createNamedParameter($parentId),
            			));
            	$query->execute();
            			 
            }
			$connection->commit();
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function searchNodesByName($name)
    {
    	$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    	$query->select('raw_data')->from($this->tableNodes)->where($query->expr()->like('name', $query->createNamedParameter("%$name%")));
    	
    	$result = $query->execute();
    	$rows = $result->fetchAll();
    	$result->closeCursor();
    	$nodes = [];
    	foreach ($rows as $row) {
    		$nodes[$row['id']] = new Node(
    				json_decode($row['raw_data'], true));
    	}
    	return $nodes;
    }
}
