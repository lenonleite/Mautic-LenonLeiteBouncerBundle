<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

class BouncerDashboardService
{
    public function __construct(private BouncerRequestStore $requestStore)
    {
    }

    /**
     * @return array{requests:int, emails:int, credits:float}
     */
    public function getUsageTotals(): array
    {
        return $this->requestStore->getUsageTotals();
    }
}
