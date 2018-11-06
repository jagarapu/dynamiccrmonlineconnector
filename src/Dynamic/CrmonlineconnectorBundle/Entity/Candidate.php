<?php
namespace Dynamic\CrmonlineconnectorBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table( name = "candidate")
 * @ORM\Entity(repositoryClass="Dynamic\CrmonlineconnectorBundle\Repository\CandidateRepository")
 *  
 */
class Candidate {
    /**
     * @ORM\Id
     * @ORM\Column(type = "integer", name= "id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    
    /**
     * @ORM\Column(type="string", name="name", length=100)
     * @Assert\NotBlank( message = "name cannot be a blank")
     * @Assert\Regex(
     *      pattern="/\d/",
     *      match=false,
     *      message="name cannot be a number" )
     */
    public $name;
    
    /**
     * @ORM\Column(type="string", name="email", length=100)
     * @Assert\NotBlank( message = "email cannot be a blank")
     * @Assert\Regex(
     *      pattern="/\d/",
     *      match=false,
     *      message="email cannot be a number" )
     */
    public $email;
    
    /**
     * @ORM\Column(type="string", name="statusreason", length=100)
     * @Assert\NotBlank( message = "statusreason cannot be a blank")
     */
    public $statusreason;
    
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
    

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Candidate
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Candidate
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set statusreason
     *
     * @param string $statusreason
     *
     * @return Candidate
     */
    public function setStatusreason($statusreason)
    {
        $this->statusreason = $statusreason;

        return $this;
    }

    /**
     * Get statusreason
     *
     * @return string
     */
    public function getStatusreason()
    {
        return $this->statusreason;
    }

    /**
     * Set createdon
     *
     * @param \DateTime $createdon
     *
     * @return Candidate
     */
    public function setCreatedon($createdon)
    {
        $this->createdon = $createdon;

        return $this;
    }

    /**
     * Get createdon
     *
     * @return \DateTime
     */
    public function getCreatedon()
    {
        return $this->createdon;
    }

    /**
     * Set program
     *
     * @param string $program
     *
     * @return Candidate
     */
    public function setProgram($program)
    {
        $this->program = $program;

        return $this;
    }

    /**
     * Get program
     *
     * @return string
     */
    public function getProgram()
    {
        return $this->program;
    }
}
