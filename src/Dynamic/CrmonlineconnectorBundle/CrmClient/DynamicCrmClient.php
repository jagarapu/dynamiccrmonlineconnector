<?php

namespace Dynamic\CrmonlineconnectorBundle\CrmClient;

use Symfony\Component\DependencyInjection\Container;
use Buzz\Browser;  
use Dynamic\CrmonlineconnectorBundle\Entity\Lead;
use Dynamic\CrmonlineconnectorBundle\Entity\Candidate;
use Dynamic\CrmonlineconnectorBundle\Entity\Newsletter;
use \Exception as Exception;

/**
 * Description of DynamicCrmClient
 *
 * @author Somesh Jagarapu
 */
class DynamicCrmClient {
    const BOOLEAN_TYPE = 0;
    const INTEGER_TYPE = 1;
    const DATETIME_TYPE = 2;
    const STRING_TYPE = 3;
    const OPTIONSET_TYPE = 4;
    const ENTITYREFERENCE_TYPE = 5;
    const DECIMAL_TYPE = 6;

    protected $container;
    protected $username;
    private $password;
    public $url;
    public $securityToken0;
    public $securityToken1;
    public $keyIdentifier;
    public $buzz;
    
    /**
     * Constructor for the Dynamic Crm Client
     * 
     * @param Container $container
     * @param Browser $buzz 
     */
    public function __construct(Container $container, Browser $buzz) {

        $this->container = $container;
        $this->buzz = $buzz;
        //Set timeout to 30 seconds for slower connections or large data
        $this->buzz->getClient()->setTimeout(15);
    }
    
    /**
     *  
     *  Gets all the required information from the config file  
     */
    public function constructDynamics() {
        
        $this->username = $this->container->getParameter('username');
        $this->password = $this->container->getParameter('password');
        $this->url = $this->container->getParameter('dynamicsurl');
    }
    
    /**
     * Logs into Microsoft Dynamics CRM
     *
     * @return true on success else error
     */
    public function doOCPAuthentication() {
        $this->username = $this->container->getParameter('username');
        $this->password = $this->container->getParameter('password');
        $this->url = $this->container->getParameter('dynamicsurl');
        
        //Step 0 - Get URN Address and STS Endpoint dynamically from WSDL
        //Using HTTP Get Method, request for the WSDL
        $wsdl = $this->getMethod($this->url . "?wsdl");
        //Get url to import the wsdl
        $wsdlImportUrl = $this->getWsdlImportUrl($wsdl);
        //import the wsdl import url
        $wsdlImport = $this->getMethod($wsdlImportUrl);
        $urnAddress = $this->getUrnAddress($wsdlImport);
        $stsEndPoint = $this->getStsEndPoint($wsdlImport);


        //Step A : Get security token by sending OCP username and password
        $securityTokenSoapTemplate = $this->getSecurityTokenSoapTemplate($urnAddress, $stsEndPoint);

        //Request for the security token
        $securityTokenResponse = $this->buzz->post($stsEndPoint, array(), $securityTokenSoapTemplate)->getContent();
        
        //Get Security tokens and key identifiers        
        try {
            $this->Securitytokens($securityTokenResponse);
        } catch (Exception $e){
            echo 'Ensure your proper credentials: ',  $e->getMessage(), "\n";
        } 
    }
   
   /**
     * This function gets Security tokens and key identifiers
     * 
     * @param type $securityTokenResponse
     * @return type 
     */ 
   public function Securitytokens($securityTokenResponse){   
        $this->securityToken0 = $this->getSecurityToken0($securityTokenResponse);
        $this->securityToken1 = $this->getSecurityToken1($securityTokenResponse);
        $this->keyIdentifier = $this->getKeyIdentifier($securityTokenResponse);
    }
   
    /**
     * This function gets and returns the STS EndPoint method
     * 
     * @param type $wsdlUrl
     * @return type 
     */
    public function getMethod($wsdlUrl) {

        $client = $this->buzz->getClient();
        $client->setOption(CURLOPT_SSLVERSION, 4);
        $client->setOption(CURLOPT_TIMEOUT, 100);

//        $client->setOption(CURLOPT_VERIFYHOST, false);
        //Setting SSL Verify certificate to false--should be handled in production
        $client->setVerifyPeer(false);

        $response = $this->buzz->get($wsdlUrl);
        $data = $response->getContent();

        return $data;
    }
    
    /**
     * Return the wsdl import url.
     * 
     * @param type $wsdl
     * @return type 
     */
    public function getWsdlImportUrl($wsdl) {
        $dom = new \DOMDocument();
        $dom->loadXML($wsdl);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='import' and namespace-uri()='http://schemas.xmlsoap.org/wsdl/']/@location");

        $importUrl = $nodeList->item(0)->nodeValue;
        return $importUrl;
    }
    
    /**
     * Gets the urn address of the wsdl url
     *
     * @param type $wsdl
     * @return type 
     */
    public function getUrnAddress($wsdl) {
        $dom = new \DOMDocument();
        $dom->loadXML($wsdl);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='AuthenticationPolicy' and namespace-uri()='http://schemas.microsoft.com/xrm/2012/Contracts/Services']/*[local-name()='SecureTokenService' and namespace-uri()='http://schemas.microsoft.com/xrm/2012/Contracts/Services']//*[local-name()='AppliesTo' and namespace-uri()='http://schemas.microsoft.com/xrm/2012/Contracts/Services']/text()");

        $importUrl = $nodeList->item(0)->nodeValue;
        return $importUrl;
    }
    
    /**
     *  Gets the STS End Point
     * 
     * @param type $wsdl
     * @return type 
     */
    public function getStsEndPoint($wsdl) {
        $dom = new \DOMDocument();
        $dom->loadXML($wsdl);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='Issuer' and namespace-uri()='http://docs.oasis-open.org/ws-sx/ws-securitypolicy/200702']/*[local-name()='Address' and namespace-uri()='http://www.w3.org/2005/08/addressing']/text()");

        $importUrl = $nodeList->item(0)->nodeValue;
        return $importUrl;
    }
    
    /**
     * Get security soap information
     *
     * @param type $urnAddress
     * @param type $stsEndPoint
     * @return string 
     */
    public function getSecurityTokenSoapTemplate($urnAddress, $stsEndPoint) {

        $securitySoapTemplate = '
            <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"
	                xmlns:a="http://www.w3.org/2005/08/addressing"
	                xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
	                <s:Header>
		                <a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue
		                </a:Action>
		                <a:MessageID>urn:uuid:' . $this->create_guid() . '</a:MessageID>
		                <a:ReplyTo>
			                <a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		                </a:ReplyTo>
		                <VsDebuggerCausalityData
			                xmlns="http://schemas.microsoft.com/vstudio/diagnostics/servicemodelsink">uIDPo4TBVw9fIMZFmc7ZFxBXIcYAAAAAbd1LF/fnfUOzaja8sGev0GKsBdINtR5Jt13WPsZ9dPgACQAA
		                </VsDebuggerCausalityData>
		                <a:To s:mustUnderstand="1">' . $stsEndPoint . '
		                </a:To>
		                <o:Security s:mustUnderstand="1"
			                xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			                <u:Timestamp u:Id="_0">
				                <u:Created>' . $this->getcurrentTime() . 'Z</u:Created>
				                <u:Expires>' . $this->getexpiresTime() . 'Z</u:Expires>
			                </u:Timestamp>
			                <o:UsernameToken u:Id="uuid-14bed392-2320-44ae-859d-fa4ec83df57a-1">
				                <o:Username>' . $this->username . '</o:Username>
				                <o:Password
					                Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $this->password . '</o:Password>
			                </o:UsernameToken>
		                </o:Security>
	                </s:Header>
	                <s:Body>
		                <t:RequestSecurityToken xmlns:t="http://schemas.xmlsoap.org/ws/2005/02/trust">
			                <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
				                <a:EndpointReference>
					                <a:Address>' . $urnAddress . '</a:Address>
				                </a:EndpointReference>
			                </wsp:AppliesTo>
			                <t:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue
			                </t:RequestType>
		                </t:RequestSecurityToken>
	                </s:Body>
                </s:Envelope>
            ';
        return $securitySoapTemplate;
    }
    
    /**
     * Get current day time
     * @return string DateTime in the UTC format as required by Dynamics CRM
     * 
     */
    public function getcurrentTime() {

        date_default_timezone_set("UTC");
        $currentTime = date("Y-m-d\TH:i:s", time()) . ".000";
        return $currentTime;
    }
    
    /**
     * Handles date fields to be sent to crm
     * 
     * @param type $param
     * @return type 
     */
    public function getDate($param) {
        $date = $param;
        if (empty($param)) {
            $date = new \DateTime();
        }
        return $date->format('Y-m-d');
    }

    /**
     * Handles date fields to be sent to crm
     * 
     * @param type $param
     * @return type 
     */
    public function getDateTime($param) {
        $date = $param;
        if (empty($param)) {
            $format = 'Y-m-d H:i:s';
            $nulldate = \DateTime::createFromFormat($format, '1970-01-01 00:00:00');
            return $nulldate->format('Y-m-d\TH:i:s');
        }
        return $date->format('Y-m-d\TH:i:s');
    }
    
    /**
     *  Returns expires request time which is 5 minutes more than the current time
     *  @return string DateTime in the UTC format as required by Dynamics CRM
     * 
     */
    public function getexpiresTime() {

        date_default_timezone_set("UTC");
        $nextDayTime = date("Y-m-d\TH:i:s", mktime(date("H"), date("i") + 5, date("s"), date("m"), date("d"), date("Y"))) . ".000";
        return $nextDayTime;
    }
    
    /**
     * Create microsoft-compatible GUID
     * @param string $namespace optional namespace
     * @return MS GUID
     *
     * Modified from http://www.php.net/manual/en/function.uniqid.php#107512
     *
     */
    private function create_guid($namespace = '') {
        static $guid = '';
        $uid = uniqid("", true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
//        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= '127.0.0.1';
        $data .= '1924';
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash, 0, 8) .
                '-' .
                substr($hash, 8, 4) .
                '-' .
                substr($hash, 12, 4) .
                '-' .
                substr($hash, 16, 4) .
                '-' .
                substr($hash, 20, 12);
        return $guid;
    }
    
   
    
    /**
     * Returns the first security token Cipher Value
     * 
     * @param type $soap
     * @return type 
     */
    public function getSecurityToken0($soap) {
        $dom = new \DOMDocument();
        $dom->loadXML($soap);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='CipherValue']/text()");
        $importUrl = $nodeList->item(0)->nodeValue;
        return $importUrl;
    }
    
    /**
     * Returns the second security token cipher value
     * 
     * @param type $soap
     * @return type 
     */
    public function getSecurityToken1($soap) {
        $dom = new \DOMDocument();
        $dom->loadXML($soap);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='CipherValue']/text()");

        $importUrl = $nodeList->item(1)->nodeValue;
        return $importUrl;
    }
    
    /**
     * Gets the Key Identifier
     * 
     * @param type $soap
     * @return type 
     */
    public function getKeyIdentifier($soap) {
        $dom = new \DOMDocument();
        $dom->loadXML($soap);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='KeyIdentifier']/text()");

        $importUrl = $nodeList->item(0)->nodeValue;
        return $importUrl;
    }
    
    /**
     * Returns the CRM Soap Header
     *
     * @param string The action string to be made to the CRM Record
     * @return string 
     */
    public function getCRMSoapHeader($action) {
        
        $soapHeader = '
                <s:Header>
                    <a:Action s:mustUnderstand="1">
                    http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/' . $action . '</a:Action>
                    <a:MessageID>urn:uuid:' . $this->create_guid() . '</a:MessageID>
                    <a:ReplyTo>
                      <a:Address>
                      http://www.w3.org/2005/08/addressing/anonymous</a:Address>
                    </a:ReplyTo>
                    <VsDebuggerCausalityData xmlns="http://schemas.microsoft.com/vstudio/diagnostics/servicemodelsink">
                    uIDPozJEz+P/wJdOhoN2XNauvYcAAAAAK0Y6fOjvMEqbgs9ivCmFPaZlxcAnCJ1GiX+Rpi09nSYACQAA
                    </VsDebuggerCausalityData>
                    <a:To s:mustUnderstand="1"> ' . $this->url . '</a:To>
                    <o:Security s:mustUnderstand="1"
                    xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                      <u:Timestamp u:Id="_0">
                        <u:Created>' . $this->getcurrentTime() . 'Z</u:Created>
                        <u:Expires>' . $this->getexpiresTime() . 'Z</u:Expires>
                      </u:Timestamp>
                      <EncryptedData Id="Assertion0"
                      Type="http://www.w3.org/2001/04/xmlenc#Element"
                      xmlns="http://www.w3.org/2001/04/xmlenc#">
                        <EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc">
                        </EncryptionMethod>
                        <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                          <EncryptedKey>
                            <EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p">
                            </EncryptionMethod>
                            <ds:KeyInfo Id="keyinfo">
                              <wsse:SecurityTokenReference xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                                <wsse:KeyIdentifier EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary"
                                ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier">
                                ' . $this->keyIdentifier . '</wsse:KeyIdentifier>
                              </wsse:SecurityTokenReference>
                            </ds:KeyInfo>
                            <CipherData>
                              <CipherValue>
                              ' . $this->securityToken0 . '</CipherValue>
                            </CipherData>
                          </EncryptedKey>
                        </ds:KeyInfo>
                        <CipherData>
                          <CipherValue>
                          ' . $this->securityToken1 . '</CipherValue>
                        </CipherData>
                      </EncryptedData>
                    </o:Security>
                  </s:Header>';
        return $soapHeader;
    }
    
    /**
     *  Returns the soap create body for the Lead Entity.
     * 
     * @param Lead $lead
     * @param type $action
     * @return string 
     */
    public function getSoapCreateLeadBody(Lead $lead) {

        $soapBody = '
         <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                    <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                        <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">' .
                $this->getFieldXml(self::STRING_TYPE, $lead->getName(), 'name') .
                $this->getFieldXml(self::STRING_TYPE, $lead->getEmail(), 'email') .
                $this->getFieldXml(self::OPTIONSET_TYPE, $lead->getStatusreason(), 'statusreason') .
                $this->getFieldXml(self::OPTIONSET_TYPE, $lead->getCreatedon(), 'createdon') .
                $this->getFieldXml(self::OPTIONSET_TYPE, $lead->getProgram(), 'program') .
                
                '</b:Attributes>
                        <b:EntityState i:nil="true"/>
                        <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
                        <b:LogicalName>lead</b:LogicalName>
                        <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                    </entity>
                    </Create>';

        return $soapBody;
    }
    
    /**
     * Returns true if the crm record is created else returns false
     * 
     * @param string $crmResponse
     * @return boolean 
     */
    public function isCrmRecordCreated($crmResponse) {

        if (empty($crmResponse)) {
            return false;
        }

        $dom = new \DOMDocument();
        $dom->loadXML($crmResponse);
        $domxpath = new \DOMXPath($dom);
        $nodeList = $domxpath->query("//*[local-name()='CreateResult']/text()");

        $idString = $nodeList->item(0)->nodeValue;

        if ($idString != '' && !empty($idString)) {
            return $idString;
        }
        return null;
    }
    
   /**
     * Returns true if the crm record is update else returns false
     * 
     * @param string $crmResponse
     * @return boolean 
     */
    public function isCrmRecordUpdated($crmResponse) {

        if (empty($crmResponse)) {
            return false;
        }
        $dom = new \DOMDocument();
        $dom->loadXML($crmResponse);
        $domxpath = new \DOMXPath($dom);
        $domxpath->registerNamespace('s', "http://www.w3.org/2003/05/soap-envelope");
        $nodeList = $domxpath->query("//*[local-name()='Body']/s:Fault/*");
        
        if ($nodeList->length) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns true if the crm record is delete else returns false
     * 
     * @param string $crmResponse
     * @return boolean 
     */
    public function isCrmRecordDelete($crmResponse) {

        if (empty($crmResponse)) {
            return false;
        }
        $dom = new \DOMDocument();
        $dom->loadXML($crmResponse);
        $domxpath = new \DOMXPath($dom);
        $domxpath->registerNamespace('s', "http://www.w3.org/2003/05/soap-envelope");
        $nodeList = $domxpath->query("//*[local-name()='Body']/s:Fault/*");
        
        
        if ($nodeList->length) {
            return false;
        }
        return true;
    }
     
    
    /**
     * Makes the SOAP call
     *
     * @param string $request Soap method
     *
     * @return result
     */
    public function sendQuery($header, $body) {

        $xml = '
            <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" 
            xmlns:a="http://www.w3.org/2005/08/addressing" 
            xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            ' . $header . '
            <s:Body>
            ' . $body . '
            </s:Body>
            </s:Envelope>';

        $headers = array(
            'Connection: Keep-Alive',
            "Content-type: application/soap+xml; charset=UTF-8",
            "Content-length: " . strlen($xml),
        );

        return $this->buzz->post($this->url, $headers, $xml)->getContent();
    }
    
    /**
     *  Returns the soap create body for the Newsletter Entity.
     * 
     * @param Newsletter $newsletter
     * @param type $action
     * @return string 
     */
    public function getSoapCreateNewsletterBody(Newsletter $newsletter) {

        $soapBody = '
         <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                    <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                        <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">' .
                $this->getFieldXml(self::STRING_TYPE, $newsletter->getEmail(), 'new_email') .
                $this->getFieldXml(self::STRING_TYPE, $newsletter->getName(), 'new_name') .
                '</b:Attributes>
                        <b:EntityState i:nil="true"/>
                        <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
                        <b:LogicalName>new_newsletter</b:LogicalName>
                        <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                    </entity>
                    </Create>';

        return $soapBody;
    }
 
    /**
     *  Returns the soap update body for the Newsletter Entity.
     * 
     * @param Newsletter $newsletter     * 
     * @return string 
     */
    public function getSoapUpdateNewsletterBody(Newsletter $newsletter) {

        $soapBody = '
         <Update xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                    <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                        <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">' .
                $this->getFieldXml(self::STRING_TYPE, $newsletter->getEmail(), 'new_email') .
                $this->getFieldXml(self::STRING_TYPE, $newsletter->getName(), 'new_name') .
                
                '</b:Attributes>
                        <b:EntityState i:nil="true"/>
                        <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        <b:Id>' . $newsletter->getNewsletteridcrm() . '</b:Id>
                        <b:LogicalName>new_newsletter</b:LogicalName>
                        <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                    </entity>
                    </Update>';

        return $soapBody;
    }
    
    /**
     * Retrieves the information regarding the candidate.
     * 
     * @param Candidate $candidate
     * @return string 
     */
    public function getSoapRetreiveRecordsBody() {

        $encode = htmlentities('<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
                                <entity name="new_newsletter">
                                  <attribute name="new_newsletterid" />
                                  <attribute name="new_name" />
                                  <attribute name="new_email" />
                                  <order attribute="new_name" descending="false" />
                                  <filter type="and">
                                    <filter type="or">
                                      <condition attribute="new_name" operator="like" value="%somesh%" />
                                      <condition attribute="new_name" operator="like" value="%utkarsh%" />
                                    </filter>
                                  </filter>
                                </entity>
                              </fetch>');
        $soapBody = '
                  <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                  <request i:type="b:RetrieveMultipleRequest" 
			 xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" 
			 xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                         <b:Parameters xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
				<b:KeyValuePairOfstringanyType>
					<c:key>Query</c:key>
					<c:value i:type="b:FetchExpression">
					<b:Query>' . $encode . '</b:Query> 
                                        </c:value>
					</b:KeyValuePairOfstringanyType>
					</b:Parameters>
					<b:RequestId i:nil="true"/>
					<b:RequestName>RetrieveMultiple</b:RequestName>
					</request>
					</Execute>';

        return $soapBody;
    }
    
    /**
     * Returns the soap delete body for th Newsletter Entity
     * 
     * @param Newsletter $newsletter
     * @return string
     */
    public function getSoapDeleteNewsletterBody(Newsletter $newsletter){
    
           $soapBody = '
                  <Delete xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                     <entityName>new_newsletter</entityName>
                     <id>' . $newsletter->getNewsletteridcrm() . '</id>                      
                  </Delete>';

        return $soapBody;  
    } 
   
    /**
     * Returns the xml field for the corresponding attribute
     *
     * @param type $type
     * @param type $fieldval
     * @param type $fieldname
     * @return string 
     */
    public function getFieldXml($type, $fieldval, $fieldname, $entityname = null) {

        $xmlString = '<b:KeyValuePairOfstringanyType>';
        $xmlString .= '<c:key>' . $fieldname . '</c:key>';

        switch ($type):
            case self::BOOLEAN_TYPE://boolean
                $xmlString .= '<c:value i:type="d:boolean" xmlns:d="http://www.w3.org/2001/XMLSchema">';
                $xmlString.= $fieldval;
                $xmlString .= '</c:value>';
                break;
            case self::DATETIME_TYPE://datetime
                $xmlString .= '<c:value i:type="d:dateTime" xmlns:d="http://www.w3.org/2001/XMLSchema">';
                $xmlString.= $fieldval;
                $xmlString .= '</c:value>';
                break;
            case self::STRING_TYPE://string
                $xmlString .= '<c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">';
                $xmlString.= htmlentities($fieldval, ENT_XML1);
                $xmlString .= '</c:value>';
                break;
            case self::OPTIONSET_TYPE://optionset
                $xmlString .= '<c:value i:type="b:OptionSetValue" xmlns:d="http://www.w3.org/2001/XMLSchema"><b:Value>';
                $xmlString.= $fieldval;
                $xmlString .= '</b:Value></c:value>';
                break;
            case self::ENTITYREFERENCE_TYPE://entity reference
                $xmlString .= '<c:value i:type="b:EntityReference"><b:Id>';
                $xmlString.= $fieldval;
                $xmlString.= '</b:Id><b:LogicalName>';
                $xmlString.= $entityname;
                $xmlString.= '</b:LogicalName><b:Name/>';
                $xmlString .= '</c:value>';
                break;
            case self::DECIMAL_TYPE://decimal type
                $xmlString .= '<c:value i:type="d:decimal" xmlns:d="http://www.w3.org/2001/XMLSchema">';
                $xmlString.= $fieldval;
                $xmlString .= '</c:value>';
                break;
            case self::INTEGER_TYPE://integer type
                $xmlString .= '<c:value i:type="d:int" xmlns:d="http://www.w3.org/2001/XMLSchema">';
                $xmlString.= $fieldval;
                $xmlString .= '</c:value>';
                break;
        endswitch;
        $xmlString .= '</b:KeyValuePairOfstringanyType>';

        return $xmlString;
    }
}
