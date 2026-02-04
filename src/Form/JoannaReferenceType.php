<?php

namespace App\Form;

use App\Entity\Bible\Book;
use App\Entity\Joanna\JoannaReference;
use App\Entity\Joanna\JoannaWork;
use App\Enum\ReferenceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JoannaReferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('work', EntityType::class, [
                'class' => JoannaWork::class,
                'choice_label' => 'title',
                'label' => 'Obra de Joanna',
            ])
            ->add('joannaChapter', TextType::class, [
                'label' => 'Capítulo (Joanna)',
            ])
            ->add('bibleBook', EntityType::class, [
                'class' => Book::class,
                'choice_label' => 'name',
                'label' => 'Livro Bíblico',
            ])
            ->add('bibleChapter', IntegerType::class, [
                'label' => 'Capítulo (Bíblia)',
            ])
            ->add('bibleVerseStart', IntegerType::class, [
                'label' => 'Versículo Início',
            ])
            ->add('bibleVerseEnd', IntegerType::class, [
                'required' => false,
                'label' => 'Versículo Fim',
            ])
            ->add('referenceType', EnumType::class, [
                'class' => ReferenceType::class,
                'label' => 'Tipo de Referência',
                'choice_label' => fn (ReferenceType $choice) => $choice->value,
            ])
            ->add('citation', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4],
                'label' => 'Citação Texto',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JoannaReference::class,
        ]);
    }
}
