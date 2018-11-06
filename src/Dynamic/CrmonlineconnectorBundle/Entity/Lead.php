<?php

namespace Dynamic\CrmonlineconnectorBundle\Entity; 

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table( name = "lead")
 *  
 */
class Lead {
    //Rating Constants
    const RATING_HOT = 1;
    const RATING_WARM = 2;
    const RATING_COLD = 3;
    
    //Status Constants 
    const STATUS_NEW = 1;
    const STATUS_CONTACTED = 2;
    const STATUS_PREQUALIFIED = 100000001;
    const STATUS_PENDINGDETAILS = 100000000;
    const STATUS_MERGEEMAIL = 100000002;
    const STATUS_MERGENAME = 100000003;
    
    //Request Status - Constants
    const REQUESTSTATUS_PENDING_APPROVAL = 100000000;
    const REQUESTSTATUS_APPROVED = 100000001;
    const REQUESTSTATUS_SENT = 100000002;
    const REQUESTSTATUS_REFUSED = 100000003;
    
    
//    public function __construct(){
//        $this->setIsotherprogram(0);
//        $this->setRequesttype(100000000);
//        $this->setCountry(100000249);        
//    }
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
     * @ORM\Column(type="integer", name="statusreason", length=9 )
     */
    protected $statusreason;

    /**
     * @ORM\Column(type="datetime", name="createdon", length=100)
     * @Assert\NotBlank( message = "createdon cannot be a blank")
     */
    public $createdon;
    
    /**
     * @ORM\Column(type="string", name="program", length=100)
     * @Assert\NotBlank( message = "program cannot be a blank")
     */
    public $program;
    
    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getEmail() {
        return $this->email;
    }

    function getStatusreason() {
        return $this->statusreason;
    }

    function getCreatedon() {
        return $this->createdon;
    }

    function getProgram() {
        return $this->program;
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

    function setStatusreason($statusreason) {
        $this->statusreason = $statusreason;
    }

    function setCreatedon($createdon) {
        $this->createdon = $createdon;
    }

    function setProgram($program) {
        $this->program = $program;
    }

}
