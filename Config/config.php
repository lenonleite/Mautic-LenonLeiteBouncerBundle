<?php

declare(strict_types=1);

return [
    'name'        => 'Lenon Leite Bouncer',
    'description' => 'Bouncer email verification and lead quality integration.',
    'version'     => '1.0.0',
    'author'      => 'Lenon Leite',
    'routes'      => [
        'main' => [
            'mautic_bouncer_dashboard' => [
                'path'       => '/bouncer/requests/{page}',
                'controller' => MauticPlugin\LenonLeiteBouncerBundle\Controller\BouncerController::class.'::indexAction',
            ],
            'mautic_bouncer_check_lead' => [
                'path'       => '/bouncer/lead/{leadId}/check',
                'controller' => MauticPlugin\LenonLeiteBouncerBundle\Controller\BouncerController::class.'::checkLeadAction',
            ],
            'mautic_bouncer_lead_details' => [
                'path'       => '/bouncer/lead/{leadId}/details',
                'controller' => MauticPlugin\LenonLeiteBouncerBundle\Controller\BouncerController::class.'::leadDetailsAction',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'lenonleitebouncer.menu.requests' => [
                'id'        => 'mautic_bouncer_dashboard',
                'route'     => 'mautic_bouncer_dashboard',
                'access'    => 'admin:plugins:plugins:view',
                'iconClass' => 'ri-mail-check-line',
                'priority'  => 5,
            ],
        ],
    ],
    'services' => [
        'integrations' => [
            'mautic.integration.lenonleitebouncer' => [
                'class' => MauticPlugin\LenonLeiteBouncerBundle\Integration\LenonLeiteBouncerIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'mautic.integration.lenonleitebouncer.configuration' => [
                'class' => MauticPlugin\LenonLeiteBouncerBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
            'mautic.integration.lenonleitebouncer.config' => [
                'class' => MauticPlugin\LenonLeiteBouncerBundle\Integration\Config::class,
                'tags'  => [
                    'mautic.integrations.helper',
                ],
                'arguments' => [
                    'mautic.integrations.helper',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
    'parameters' => [
        'bouncer_active'          => false,
        'bouncer_api_key'         => '',
        'bouncer_partner_url'     => 'https://withlove.usebouncer.com/ioxhfxgs6zii',
        'bouncer_check_on_create' => false,
        'bouncer_batch_size'      => 100,
        'bouncer_sync_limit'      => 20,
    ],
];
