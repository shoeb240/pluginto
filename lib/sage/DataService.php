<?php
/**
* This file contains DataService performs CRUD operations on IPP REST APIs.
*/
class DataService
{
	/**
	 * The Service user id.
	 * @var userId 
	 */
	private $userId;
        
        /**
	 * The Service user password.
	 * @var userPass 
	 */
	private $userPass;
        
        /**
	 * The Service api key.
	 * @var apiKey 
	 */
	private $apiKey;
        
        /**
	 * The Service company id.
	 * @var companyId 
	 */
	private $companyId;
        
	/**
	 * Initializes a new instance of the DataService class.
	 *
	 * @param ServiceContext $serviceContext IPP Service Context
	 */
	public function __construct($userId, $userPass, $apiKey, $companyId)
	{
		if (NULL == $userId)
		{
			throw new \Exception('User id missing');
		}
                
                if (NULL == $userPass)
		{
			throw new \Exception('User password missing');
		}
                
                if (NULL == $apiKey)
		{
			throw new \Exception('Api key missing');
		}
		
		$this->userId = $userId;
                $this->userPass = $userPass;
                $this->apiKey = $apiKey;
                $this->companyId = $companyId;
		
	}
        
        private function getEndPoint()
        {
            return "https://accounting.sageone.co.za/api/1.1.0";
        }
        
        public function setCompanyId($companyId)
        {
            $this->companyId = $companyId;
        }
	
        private function sendRequest($httpMethod, $url, $entity = null)
        {
            $encodedAuth = base64_encode($this->userId . ':' . $this->userPass);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
            curl_setopt($ch, CURLOPT_POST, $httpMethod);
            if ($httpMethod) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
                curl_setopt($ch, CURLOPT_POSTFIELDS, $entity); 
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization : Basic " . $encodedAuth,
                'Content-Type: application/json',
                    'Accept: application/json'
                ));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            //curl_setopt($ch, CURLOPT_HEADER, 1);
            //curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

            $result = curl_exec($ch);
            
            $getinfo = curl_getinfo($ch);
//            //print_r($getinfo['request_header']);
//            //echo "\r\n\r\n";
//            //echo $entity . '<br />';
//            echo '<p>curlresult start:';
//            print_r($result);
//            echo '<p>curlresult end<br/>';
            
            return json_decode($result);
        }
        
        
	/**
	 * Updates an entity under the specified realm. The realm must be set in the context.
	 *
	 * @param IPPIntuitEntity $entity Entity to Update.
	 * @return IPPIntuitEntity Returns an updated version of the entity with updated identifier and sync token. 
	 */
	public function Update($entity)
	{
		$this->serviceContext->IppConfiguration->Logger->RequestLog->Log(TraceLevel::Info, "Called Method Update.");
	
        // Validate parameter
		if (!$entity)
		{
			$this->serviceContext->IppConfiguration->Logger->RequestLog->Log(TraceLevel::Error, "Argument Null Exception");
			throw new IdsException('Argument Null Exception');
		}

		$httpsPostBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($entity, $urlResource);
		
		// Builds resource Uri
		// Handle some special cases
		if ((strtolower('preferences')==strtolower($urlResource)) &&
		    (IntuitServicesType::QBO==$this->serviceContext->serviceType))
		{		    
			// URL format for *QBO* prefs request is different than URL format for *QBD* prefs request
			$uri = implode(CoreConstants::SLASH_CHAR,array('company', $this->serviceContext->realmId, $urlResource));
		}
		else if ((strtolower('company')==strtolower($urlResource)) &&
		         (IntuitServicesType::QBD==$this->serviceContext->serviceType))
		{		    
			// URL format for *QBD* companyinfo request is different than URL format for *QBO* companyinfo request
			$urlResource='companyInfo';
			$uri = implode(CoreConstants::SLASH_CHAR,array('company', $this->serviceContext->realmId, $urlResource.'?operation=update'));
		}
		else
		{
			// Normal case
			$uri = implode(CoreConstants::SLASH_CHAR,array('company', $this->serviceContext->realmId, $urlResource.'?operation=update'));
		}

        // Creates request parameters
		if ($this->serviceContext->IppConfiguration->Message->Request->SerializationFormat == SerializationFormat::Json)
		{
			$requestParameters = new RequestParameters($uri, 'POST', CoreConstants::CONTENTTYPE_APPLICATIONJSON, NULL);
		}
		else
		{
			$requestParameters = new RequestParameters($uri, 'POST', CoreConstants::CONTENTTYPE_APPLICATIONXML, NULL);
		}

		$restRequestHandler = new SyncRestHandler($this->serviceContext);
		try
		{
		    // gets response
			list($responseCode,$responseBody) = $restRequestHandler->GetResponse($requestParameters, $httpsPostBody, NULL);
		}
		catch (Exception $e)
		{
                    // added by shoeb
                    throw new \Exception($e->getMessage());
		}		
		
        CoreHelper::CheckNullResponseAndThrowException($responseBody);

		try {
			$parsedResponseBody = $this->responseSerializer->Deserialize($responseBody, TRUE);		                                                            
		}
		catch (Exception $e) {
			return NULL;
		}		

		$this->serviceContext->IppConfiguration->Logger->RequestLog->Log(TraceLevel::Info, "Finished Executing Method Update.");
		return $parsedResponseBody;
    }
    


	/**
	 * Returns an entity under the specified realm. The realm must be set in the context.
	 *
	 * @param object $entity Entity to Find
	 * @return IPPIntuitEntity Returns an entity of specified Id. 
	 */	
	public function FindById($entityName, $id)
	{
            if ($entityName == 'Company') {
                $req = "/Company/Get/{$id}?apikey=" . $this->apiKey;
            } else {
                $req = "/{$entityName}/Get/{$id}?apikey=" . $this->apiKey . "&CompanyId=" . $this->companyId;
            }
            
            
            $url = $this->getEndPoint() . $req;
            
            return $this->sendRequest(0, $url);
	}
	
	/**
	 * Returns an entity under the specified realm. The realm must be set in the context.
	 *
	 * @param object $entity Entity to Find
	 * @return IPPIntuitEntity Returns an entity of specified Id. 
	 */	
	public function Retrieve($entity)
	{
		return $this->FindById($entity);
	}
	
	/**
	 * Creates an entity under the specified realm. The realm must be set in the context.
	 *
	 * @param IPPIntuitEntity $entity Entity to Create.
	 * @return IPPIntuitEntity Returns the created version of the entity. 
	 */
	public function Add($entityName, $entity)
	{
            $req = "/{$entityName}/Save?apikey=" . $this->apiKey . "&CompanyId=" . $this->companyId;
            $url = $this->getEndPoint() . $req;
            
            return $this->sendRequest(1, $url, $entity);
	}    

	

	/**
	 * Retrieves specified entities based passed page number and page size and query
	 *
	 * @param string $query Query to issue
	 * @param string $pageNumber Starting page number
	 * @param string $pageSize Page size
	 * @return array Returns an array of entities fulfilling the query. 
	 */	
	public function Query($query, $pageNumber=0, $pageSize=500)
	{
		$this->serviceContext->IppConfiguration->Logger->RequestLog->Log(TraceLevel::Info, "Called Method Query.");
	
		if ('QBO'==$this->serviceContext->serviceType)
			$httpsContentType = CoreConstants::CONTENTTYPE_APPLICATIONTEXT;
		else
			$httpsContentType = CoreConstants::CONTENTTYPE_TEXTPLAIN;
		
		$httpsUri = implode(CoreConstants::SLASH_CHAR,array('company', $this->serviceContext->realmId, 'query'));
		$httpsPostBody = $query;

		$requestParameters = new RequestParameters($httpsUri, 'POST', $httpsContentType, NULL);
		$restRequestHandler = new SyncRestHandler($this->serviceContext);
		list($responseCode,$responseBody) = $restRequestHandler->GetResponse($requestParameters, $httpsPostBody, NULL);
		
		$parsedResponseBody = NULL;
		try {
			$responseXmlObj = simplexml_load_string($responseBody);
			if ($responseXmlObj && $responseXmlObj->QueryResponse)	                                                            
				$parsedResponseBody = $this->responseSerializer->Deserialize($responseXmlObj->QueryResponse->asXML(), FALSE);		                                                            
		}
		catch (Exception $e) {
			return NULL;
		}		
		return $parsedResponseBody;
	}

	/**
	 * Retrieves specified entity based passed page number and page size
	 *
	 * @param string $urlResource Entity type to Find
	 * @return array Returns an array of entities of specified type. 
	 */	
	public function FindAll($entityName, $pageNumber=0, $pageSize=500)
	{
            //$entityName = 'TaxInvoice'; // remove
            if ($entityName == 'Company') {
                $req = "/Company/Get?apikey=" . $this->apiKey;
            } else {
                $req = "/{$entityName}/Get?apikey=" . $this->apiKey . "&CompanyId=" . $this->companyId;
            }
            $url = $this->getEndPoint() . $req;
            
            return $this->sendRequest(0, $url);

	}
        
        
        public function FindReport($entityName, $entity)
	{
            if ($entityName == 'Company') {
                $req = "/Company/Get?apikey=" . $this->apiKey;
            } else {
                $req = "/{$entityName}/Get?apikey=" . $this->apiKey . "&CompanyId=" . $this->companyId;
            }
            $url = $this->getEndPoint() . $req;
            
            return $this->sendRequest(1, $url, $entity);

	}


}
?>
