<?php

namespace Dynamic\CrmonlineconnectorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;    
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Epita\CrmonlineconnectorBundle\Entity\Candidate;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
//use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
//use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class CandidateType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('name', TextType::class, array(    
            'label' => 'Name',
            'required' => true,
            'attr' => array('class' => 'large_text'),
        ));       
        $builder->add('statusreason', TextType::class, array(    
            'label' => 'Statusreason',
            'required' => true,
            'attr' => array('class' => 'large_text'),
        ));    
        
        $builder->add('createdon', DateType::class, array(
            'attr' => array('class' => 'large_text'),
            'label' => 'Createdon',
            'required' => true,
            'attr' => array('class' => 'large_text'),
        ));
        
        //Email Address
        $builder->add('email', EmailType::class, array(
            'attr' => array('class' => 'large_text'),
            'label' => 'Email address',
            'required' => true,
            'error_bubbling' => false,
            'attr' => array('class' => 'large_text'),
        ));
        
        $builder->add('program', TextType::class, array(    
            'label' => 'Program',
            'required' => true,
            'attr' => array('class' => 'large_text'),
        )); 
        
    }
}
