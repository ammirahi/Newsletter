<?php

namespace App\Form;

use App\Entity\Newsletters\Categories;
use App\Entity\Newsletters\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterEditUsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('first_name', TypeTextType::class, ['required' => false])
        ->add('email', EmailType::class)
        ->add('categories', EntityType::class, [
            'class' => Categories::class,
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true
        ])
        ->add('modifier', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}
