<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('bouncer_active', YesNoButtonGroupType::class, [
            'label'      => 'lenonleitebouncer.config.form.active.label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('bouncer_api_key', TextType::class, [
            'label'      => 'lenonleitebouncer.config.form.api_key.label',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'empty_data' => '',
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'lenonleitebouncer.config.form.api_key.tooltip',
            ],
        ]);

        $builder->add('bouncer_check_on_create', YesNoButtonGroupType::class, [
            'label'      => 'lenonleitebouncer.config.form.check_on_create.label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('bouncer_batch_size', IntegerType::class, [
            'label'      => 'lenonleitebouncer.config.form.batch_size.label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
                'min'   => 1,
            ],
        ]);

        $builder->add('bouncer_sync_limit', IntegerType::class, [
            'label'      => 'lenonleitebouncer.config.form.sync_limit.label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
                'min'   => 1,
            ],
        ]);
    }
}
