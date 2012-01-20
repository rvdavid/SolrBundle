<?php
namespace FS\SolrBundle;

use FS\SolrBundle\Doctrine\Annotation\AnnotationReader;

use FS\SolrBundle\Doctrine\Mapper\Command\CreateFromExistingDocumentCommand;

use FS\SolrBundle\Doctrine\Mapper\Command\CreateFreshDocumentCommand;

use FS\SolrBundle\Doctrine\Mapper\EntityMapper;

class SolrFacade {
	private $hostname;
	
	private $port;
	
	/**
	 * 
	 * @var \SolrClient
	 */
	private $solrClient = null;
	
	/**
	 * 
	 * @var EntityMapper
	 */
	private $entityMapper = null;
	
	public function __construct($hostname, $port) {
		$this->solrClient = new \SolrClient(array(
				'hostname' => $hostname,
				'port'	   => $port
		));
		
		$this->entityMapper = new EntityMapper();
	}	
	
	public function updateDocument($entity) {
		$this->entityMapper->setMappingCommand(new CreateFromExistingDocumentCommand());
		
		$this->addDocumentToIndex($entity);		
	}
	
	public function removeDocument($entity) {
		$this->entityMapper->setMappingCommand(new CreateFromExistingDocumentCommand());
		
		$this->addDocumentToIndex($entity);
	}
	
	public function addDocument($entity) {
		$this->entityMapper->setMappingCommand(new CreateFreshDocumentCommand(new AnnotationReader()));
		
		$this->addDocumentToIndex($entity);
	}
	
	private function addDocumentToIndex($entity) {
		$doc = $this->entityMapper->toDocument($entity);
		$updateResponse = $this->solrClient->addDocument($doc);
		
		$this->solrClient->commit();		
	}
	
	/**
	 * 
	 * @return \SolrResponse
	 */
	public function query(SolrQuery $query) {
		$solrQuery = $query->getSolrQuery();
		$response = $this->solrClient->query($solrQuery);
		
		$response = $response->getResponse();

		$targetEntity = $query->getEntity();
		$mappedEntities = array();
		if (array_key_exists('response', $response)) {
			if ($response['response']['docs'] !== false) {
				foreach ($response['response']['docs'] as $document) {
					$mappedEntities[] = $this->entityMapper->toEntity($document, $targetEntity);
				}
			}
		}
		
		return $mappedEntities;
	}
}