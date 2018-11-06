<?php
namespace Dynamic\CrmonlineconnectorBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Dynamic\CrmonlineconnectorBundle\Entity\Newsletter;
use Dynamic\CrmonlineconnectorBundle\Form\Type\NewsletterType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dynamic\CrmonlineconnectorBundle\Library\ConsumerService;


class CandidateController extends Controller{
    
    /**
     * @Route("/register", name="registrationpage")
     */    
    public function registrationAction(Request $request){
        $newsletter = new Newsletter();
        $form = $this->createForm(NewsletterType::class, $newsletter);     
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {           
            $newsletter = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();
            $this->addNewsletter($newsletter);
            $url = $this->generateUrl('successpage');
            $response = new RedirectResponse($url);
            return $response;
        }
        
        return $this->render('DynamicCrmonlineconnectorBundle:Registration:register.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    /**
     * @Route("/list", name="listpage")
     */  
    public function ListAction(){
       $em = $this->getDoctrine()->getManager();
       $newsletter = $em->getRepository('DynamicCrmonlineconnectorBundle:Newsletter')->findAll();   
       return $this->render('DynamicCrmonlineconnectorBundle:Registration:list.html.twig', array(
                        'newsletters' => $newsletter,
            ));
        
    }
    
    /**
     * @Route("/edit/{id}", requirements={"id" = "\d+"}, name="editpage")
     */
    public function editAction(Request $request, $id){ 
      $em = $this->getDoctrine()->getManager();
      $newsletter = $em->getRepository('DynamicCrmonlineconnectorBundle:Newsletter')->findOneBy(array('id' => $id));
      $form = $this->createForm(NewsletterType::class, $newsletter);  
      $form->handleRequest($request);
      
      if ($form->isSubmitted() && $form->isValid()) {           
            $newsletter = $form->getData();
            $em->persist($newsletter);
            $em->flush();
            $this->updateNewsletter($newsletter);
            $url = $this->generateUrl('listpage');
            $response = new RedirectResponse($url);
            return $response;
        }
        
        return $this->render('DynamicCrmonlineconnectorBundle:Registration:editpage.html.twig', array(
                    'form' => $form->createView(),
                    'id' => $id,
        ));  
    } 
    
    /**
     * @Route("/delete/{id}", requirements={"id" = "\d+"}, name="deletepage")
     */
    public function deleteAction(Request $request, $id){ 
      $em = $this->getDoctrine()->getManager();
      $newsletter = $em->getRepository('DynamicCrmonlineconnectorBundle:Newsletter')->findOneBy(array('id' => $id));
      $this->deleteNewsletter($newsletter);
      $em->remove($newsletter);
      $em->flush();
      return $this->render('DynamicCrmonlineconnectorBundle:Registration:deletepage.html.twig');  
    } 
    
    /**
     * @Route("/retrivemultiple", name="retrivemultiplepage")
     */
    public function retriveAction(Request $request) {
           
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        $soapheader = $crmclient->getCRMSoapHeader('Execute');
        $soapbody = $crmclient->getSoapRetreiveRecordsBody();
        
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);
        
        $dom = new \DOMDocument();
        $dom->loadXML($serverResponse);

        $domxpath = new \DOMXPath($dom);
        $domxpath->registerNamespace('b', "http://schemas.microsoft.com/xrm/2011/Contracts");
        $domxpath->registerNamespace('c', "http://schemas.datacontract.org/2004/07/System.Collections.Generic");
        $username = $domxpath->query("//*[local-name()='ExecuteResult']/b:Results/b:KeyValuePairOfstringanyType[c:key='EntityCollection']/c:value/b:Entities/b:Entity/b:Attributes/b:KeyValuePairOfstringanyType[c:key='new_name']/c:value/text()");
        $useremails = $domxpath->query("//*[local-name()='ExecuteResult']/b:Results/b:KeyValuePairOfstringanyType[c:key='EntityCollection']/c:value/b:Entities/b:Entity/b:Attributes/b:KeyValuePairOfstringanyType[c:key='new_email']/c:value/text()");
        
        if ($username->length > 0) {
            $users = array();
            $emails = array();
            foreach ($username as $node) {
                $users[] = $node->nodeValue;
            }
            foreach ($useremails as $node) {
                $emails[] = $node->nodeValue;
            }
            return $this->render('DynamicCrmonlineconnectorBundle:Registration:retrive.html.twig', array(
                    'users' => $users,
                    'emails' => $emails,
        ));  
        }
        return new Response(
            'No users'
        );
    }
    
    /**
     * @Route("/connectcrm", name="connectcrm")
     */  
    public function connectcrmAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        //There will be newsletter id sent for this method, so retrieve it
//        $newsletterid = 1;
        $crmclient = $this->container->get('crmclient_utility');
//        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        return new Response(
            'Dynamic crm Connected Successfully...'
        );
        
//        
//        //Pass the action for the crm record
//        $soapheader = $crmclient->getCRMSoapHeader('Create');
//        $newsletter = $em->getRepository('DynamicCrmonlineconnectorBundle:Newsletter', $newsletterid);
//        $soapbody = $crmclient->getSoapCreateNewsletterBody($newsletter);
//        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);
//
//        //Retrieve response and update the crm record
//        $iscrmrecord = $crmclient->isCrmRecordCreated($serverResponse);
//
//        if ($iscrmrecord) {
//            return new Response('Success');
//        } else {
//            return FALSE;
//        }
        
    }
    
    
    /**
     * This function adds newsletter record to the crm using Rabbit MQ.
     * @param array $body
     * @return boolean 
     */
    private function addNewsletter($body) {
        //There will be candidate id sent for this method, so retrieve it
        $em = $this->getDoctrine()->getManager();
        $newsletterid = $body->getId();
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Create');
        $newsletter = $em->find('DynamicCrmonlineconnectorBundle:Newsletter', $newsletterid);
        $soapbody = $crmclient->getSoapCreateNewsletterBody($newsletter);
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);
        
        //Retrieve response and update the crm record
        $iscrmrecord = $crmclient->isCrmRecordCreated($serverResponse);

        if ($iscrmrecord) {
            $newsletter->setIscrmrecord(true);
            $newsletter->setNewsletteridcrm($iscrmrecord);
            $em->persist($newsletter);
            $em->flush();
        } else {
            return false;
        }
        return TRUE;
        
    }
    
    /**
     * This function delete the candidate personal information for airport, accomodation and pickup.
     * 
     * @param array $body
     * @return boolean 
     */
    private function deleteNewsletter($body) {

        $em = $this->getDoctrine()->getManager();
        $newsletterid = $body->getId();
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Delete');
        $newsletter = $em->find('DynamicCrmonlineconnectorBundle:Newsletter', $newsletterid);
        $soapbody = $crmclient->getSoapDeleteNewsletterBody($newsletter);
        
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);
        
        //Verify if the record was successfully deleted
        $iscrmdelete = $crmclient->isCrmRecordDelete($serverResponse);
        
        if ($iscrmdelete) {
            return TRUE;
        } else {
            return false;
        }
    }
    
    /**
     * This function updates the candidate personal information for airport, accomodation and pickup.
     * 
     * @param array $body
     * @return boolean 
     */
    private function updateNewsletter($body) {

        $em = $this->getDoctrine()->getManager();
        $newsletterid = $body->getId();
        $crmclient = $this->container->get('crmclient_utility');
        $crmclient->constructDynamics();
        $crmclient->doOCPAuthentication();
        //Pass the action for the crm record
        $soapheader = $crmclient->getCRMSoapHeader('Update');
        $newsletter = $em->find('DynamicCrmonlineconnectorBundle:Newsletter', $newsletterid);
        $soapbody = $crmclient->getSoapUpdateNewsletterBody($newsletter);
        $serverResponse = $crmclient->sendQuery($soapheader, $soapbody);
   
        //Verify if the record was successfully updated
        $iscrmupdated = $crmclient->isCrmRecordUpdated($serverResponse);

        if ($iscrmupdated) {
            return TRUE;
        } else {
            return false;
        }
    }
    
    /**
     * @Route("/success", name="successpage")
     */
    public function successAction(){
      return $this->render('DynamicCrmonlineconnectorBundle:Registration:success.html.twig');     
    }
    
}   