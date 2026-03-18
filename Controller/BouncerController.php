<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerDashboardService;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerRequestStore;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class BouncerController extends AbstractFormController
{
    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function indexAction(Request $request, BouncerRequestStore $requestStore, BouncerDashboardService $dashboardService, Config $config, int $page = 1): Response
    {
        if (!$this->security->isGranted('admin:plugins:plugins:view')) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters' => [
                'items'      => $requestStore->getRecent($page, 25),
                'totals'     => $dashboardService->getUsageTotals(),
                'page'       => $page,
                'partnerUrl' => $config->getPartnerUrl(),
                'hasApiKey'  => '' !== $config->getApiKey(),
            ],
            'contentTemplate' => '@LenonLeiteBouncer/Bouncer/dashboard.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_bouncer_dashboard',
                'mauticContent' => 'bouncer',
            ],
        ]);
    }

    public function checkLeadAction(Request $request, BouncerVerificationService $verificationService, int $leadId): Response
    {
        $lead = $this->getLeadOr404($leadId);
        $detailsUrl = $this->generateUrl('mautic_bouncer_lead_details', ['leadId' => $leadId]);

        if (in_array($request->getMethod(), ['GET', 'POST'], true)) {
            $verificationService->verifyLead($lead);
            $this->addFlashMessage('lenonleitebouncer.flash.checked', ['%email%' => (string) $lead->getEmail()]);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'redirect' => $detailsUrl,
                ]);
            }

            return $this->redirect($detailsUrl);
        }

        return new Response('Method Not Allowed', 405);
    }

    public function leadDetailsAction(Config $config, int $leadId): Response
    {
        $lead           = $this->getLeadOr404($leadId);
        $leadRepository = $this->doctrine->getManager()->getRepository(Lead::class);
        $lead->setFields($leadRepository->getFieldValues($lead->getId()));

        return $this->delegateView([
            'viewParameters' => [
                'lead'        => $lead,
                'partnerUrl'  => $config->getPartnerUrl(),
                'status'      => $lead->getFieldValue('bouncer_status'),
                'score'       => $lead->getFieldValue('bouncer_score'),
                'reason'      => $lead->getFieldValue('bouncer_reason'),
                'toxic'       => $lead->getFieldValue('bouncer_toxic'),
                'toxicity'    => $lead->getFieldValue('bouncer_toxicity'),
                'provider'    => $lead->getFieldValue('bouncer_provider'),
                'rawResponse' => $lead->getFieldValue('bouncer_raw_response'),
            ],
            'contentTemplate' => '@LenonLeiteBouncer/Bouncer/lead_details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contact_index',
                'mauticContent' => 'lead',
            ],
        ]);
    }

    private function getLeadOr404(int $leadId): Lead
    {
        $lead = $this->doctrine->getManager()->getRepository(Lead::class)->find($leadId);
        if (!$lead instanceof Lead) {
            throw $this->createNotFoundException(sprintf('Lead %d not found.', $leadId));
        }

        return $lead;
    }
}
