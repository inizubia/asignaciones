<?php

namespace IZJ\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class TaskType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class)
                ->add('description', TextType::class)
                ->add('user', EntityType::class, array(
                    'class' => 'IZJUserBundle:User',
                    'query_builder' => function (EntityRepository $er){
                        return $er->createQueryBuilder('u')
                            -> where('u.role = :only')
                            -> setParameter('only', 'ROLE_USER');
                    },
                    'choice_label' => 'getFullName' 
                ))
                ->add('save', SubmitType::class, array('label' => 'Save task'));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'IZJ\UserBundle\Entity\Task'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'task';
    }


}
