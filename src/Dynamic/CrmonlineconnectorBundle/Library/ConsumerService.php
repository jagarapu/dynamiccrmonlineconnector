<?php

namespace Dynamic\CrmonlineconnectorBundle\Library;

use Symfony\Component\DependencyInjection\Container;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Monolog\Logger;

/**
 * This class implements the ConsumerInterface to execute the consumer method.
 * 
 */
class ConsumerService implements ConsumerInterface {
   
    public $container;
    public $em;
    public $logger;

    public function __construct(Container $container, EntityManager $em, Logger $logger) {
        $this->container = $container;
        $this->em = $em;
        $this->logger = $logger;
    }
  
    
    public function execute(AMQPMessage $msg) {
        
        $msgbody = (unserialize($msg->body));
        $messg = $msgbody->body;
        $method = $messg['method'];
        
        //Invoke the corresponsing method
        if (!empty($method)) {
            $ret = $this->$method($messg);
        }
        return $ret;
    }

    /**
     * This function adds candidate record to the crm using Rabbit MQ.
     * @param array $body
     * @return boolean 
     */
    private function addCandidate($body) {
        //There will be candidate id sent for this method, so retrieve it
        $candidateid = $body['candidateid'];
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();   
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Create');
        $candidate = $this->em->find('DynamicCrmonlineconnectorBundle:Candidate', $candidateid);
        $soapbody = $crmclient->getSoapCreateCandidateBody($candidate);
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);

//        $this->logger->addInfo($serverResponse);

        //Retrieve id and update the crm record
        $iscrmrecord = $crmclient->isCrmRecordCreated($serverResponse);

        if ($iscrmrecord) {
            $candidate->setIscrmrecord(true);
            $candidate->setCandidateidcrm($iscrmrecord);
            $this->em->persist($candidate);
            $this->em->flush();
        } else {
            return false;
        }
        return TRUE;
    }
    
    /**
     * This function adds lead record to the crm using Rabbit MQ.
     * @param array $body
     * @return boolean 
     */
    private function addLead($body) {
        //There will be candidate id sent for this method, so retrieve it
        $leadid = $body['leadid'];
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Create');
        $lead = $this->em->find('DynamicCrmonlineconnectorBundle:Lead', $leadid);
        $soapbody = $crmclient->getSoapCreateLeadBody($lead);
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);

//        $this->logger->addInfo($serverResponse);

        //Retrieve response and update the crm record
        $iscrmrecord = $crmclient->isCrmRecordCreated($serverResponse);

        if ($iscrmrecord) {
            $lead->setIscrmrecord(TRUE);
            $lead->setCrmcreatedon(new \DateTime("now", new \DateTimeZone(date_default_timezone_get())));
            $this->em->flush();
        } else {
            return FALSE;
        }
        return TRUE;
    }
    
    /**
     * This function adds newsletter record to the crm using Rabbit MQ.
     * @param array $body
     * @return boolean 
     */
    private function addNewsletter($body) {

        //There will be candidate id sent for this method, so retrieve it
        $newsletterid = $body['newsletterid'];
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Create');
        $newsletter = $this->em->find('DynamicCrmonlineconnectorBundle:Newsletter', $newsletterid);
        $soapbody = $crmclient->getSoapCreateNewsletterBody($newsletter);
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);

//        $this->logger->addInfo($serverResponse);

        //Retrieve response and update the crm record
        $iscrmrecord = $crmclient->isCrmRecordCreated($serverResponse);

        if ($iscrmrecord) {
//            $newsletter->setIscrmrecord(TRUE);
//            $newsletter->setCrmcreatedon(new \DateTime("now", new \DateTimeZone(date_default_timezone_get())));
//            $this->em->flush();
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
} 