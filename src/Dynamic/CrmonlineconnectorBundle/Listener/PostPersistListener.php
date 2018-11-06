<?php

namespace Dynamic\CrmonlineconnectorBundle\Listener;

use Dynamic\CrmonlineconnectorBundle\Entity\Candidate;
use Dynamic\CrmonlineconnectorBundle\Entity\Lead;
use Dynamic\CrmonlineconnectorBundle\Entity\Newsletter;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Bridge\Monolog\Logger;


class PostPersistListener {
    protected $container;
    protected $logger;

    /**
     * The post persist listener class.
     * 
     * @param Container $container
     * @param Logger $logger
     * @param \Symfony\Component\Routing\Router $router 
     */
    public function __construct(Container $container, Logger $logger) {
        $this->container = $container;
        $this->logger = $logger;
    }
    
    /**
     *  This function adds crm lead record to the microsoft dynamics crm online
     *
     * @param object $em The entity manager object
     * @param object $user The lead object
     * @return void 
     */
    private function addCrmLeadRecord($em, Lead $lead) {
               
        $msg_body = array('leadid' => $lead->getId(),'method' => 'addLead');
        $msg = new \PhpAmqpLib\Message\AMQPMessage($msg_body,array('delivery_mode' => 2));
        $producer = $this->container->get('old_sound_rabbit_mq.upload_picture_producer');
        $producer->setContentType('application/json');        
        $producer->publish(serialize($msg));    

    }
    
    /**
     *  This function adds crm Newsletter record to the microsoft dynamics crm online
     *
     * @param object $em The entity manager object
     * @param object $user The Newsletter object
     * @return void 
     */
    private function addCrmNewsletterRecord($em, Newsletter $newsletter) {        
        
        $msg_body = array('newsletterid' => $newsletter->getId(),'method' => 'addNewsletter');
//        $msg = new \PhpAmqpLib\Message\AMQPMessage($msg_body,array('delivery_mode' => 2));
//        $producer = $this->container->get('old_sound_rabbit_mq.upload_picture_producer');
//        $producer->setContentType('application/json');        
//        $producer->publish(serialize($msg));    
    }
    
    
    /**
     *  This function adds crm candidate record to the microsoft dynamics crm online
     *
     * @param object $em The entity manager object
     * @param object $user The lead object
     * @return void 
     */
    private function createCandidateSharePointFolder($em, Candidate $candidate) {
        //create candidate folder crm
//      $msg = array('candidateid' => $candidate->getId(),'method' => 'createFolder');   
//      $producer = $this->container->get('old_sound_rabbit_mq.upload_picture_producer');
//      $producer->setContentType('application/json');
//      $producer->publish(serialize($msg));
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $dirPath = $this->container->get('kernel')->getRootDir() . $this->container->getParameter('file_upload_path') . $candidate->getName();
        if (!$fs->exists($dirPath)) {
            $fs->mkdir($dirPath);
        }
    }
    
    /**
     *  This function creates sharepoint folder for the candidate
     *
     * @param object $em The entity manager object
     * @param object $user The lead object
     * @return void 
     */
    private function addCrmCandidateRecord(Candidate $candidate) {
        $msg_body = array('candidateid' => $candidate->getId(),'method' => 'addCandidate');
        $msg = new \PhpAmqpLib\Message\AMQPMessage($msg_body,array('delivery_mode' => 2));
//        $producer = $this->container->get('old_sound_rabbit_mq.upload_picture_producer');
//        $producer->setContentType('application/json');        
//        $producer->publish(serialize($msg));
    }
    
    /**
     *  The post persist operation is invoked for all insert operations to the entities.This method  is used to 
     *  add some events while inserting into the Lead and Candidate entities.
     *  
     * @param LifecycleEventArgs $args 
     */
    public function postPersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();
        
        if ($entity instanceof Lead) {
            $this->addCrmLeadRecord($em, $entity);
        }
        
        if ($entity instanceof Newsletter) {
            $this->addCrmNewsletterRecord($em, $entity);
        }
        
        if ($entity instanceof Candidate) {
            
            $this->addCrmCandidateRecord($entity);
            $this->createCandidateSharePointFolder($em, $entity);
        }
        
    }
    
}
