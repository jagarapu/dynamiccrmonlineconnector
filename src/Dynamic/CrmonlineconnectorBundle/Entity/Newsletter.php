<?php

namespace Dynamic\CrmonlineconnectorBundle\Entity; 

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table( name = "newsletter")
 *  
 */
class Newsletter{
    /**
     * @ORM\Id
     * @ORM\Column(type = "integer", name= "id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="name", length=100)
     * @Assert\NotBlank( message = "name cannot be blank")
     * @Assert\Regex(
     *      pattern="/\d/",
     *      match=false,
     *      message="name cannot be a number" )
     */
    public $name;

    /**
     * @ORM\Column(type="string", name="email", length=255 )
     * @Assert\Email( message = "email not valid")
     * @Assert\NotBlank( message = "email cannot be blank")
     * 
     */
    public $email;
    
    /**
     * @ORM\Column(type="string", name="unsubscribe", length=9 )
     */
    public $unsubscribe;

    /**
     * @ORM\Column(type="datetime", name="createdon", length=100)
     * @Assert\NotBlank( message = "createdon cannot be a blank")
     */
    public $createdon;
    
    /**
     * @ORM\Column(type="string", name="newsletteridcrm", length=60, nullable=true)
     */
    public $newsletteridcrm;
    
    /**
     * @ORM\Column(type="boolean", name="iscrmrecord")
     */
    public $iscrmrecord = FALSE;
    
    
    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getEmail() {
        return $this->email;
    }

    function getUnsubscribe() {
        return $this->unsubscribe;
    }

    function getCreatedon() {
        return $this->createdon;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setUnsubscribe($unsubscribe) {
        $this->unsubscribe = $unsubscribe;
    }

    function setCreatedon($createdon) {
        $this->createdon = $createdon;
    }
    
    function getNewsletteridcrm() {
        return $this->newsletteridcrm;
    }

    function setNewsletteridcrm($newsletteridcrm) {
        $this->newsletteridcrm = $newsletteridcrm;
    }
    
    function getIscrmrecord() {
        return $this->iscrmrecord;
    }

    function setIscrmrecord($iscrmrecord) {
        $this->iscrmrecord = $iscrmrecord;
    }

    
}   