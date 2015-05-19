<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\DataTypes\ApiContactSuppressionList;
use DotMailer\Api\Resources\IResources;

class UnsubscribedFromAccountContactsIterator extends AbstractIterator
{
    /**
     * @var IResources
     */
    protected $resources;

    /**
     * @var array
     */
    protected $addressBooks;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * @param IResources $resources
     * @param \DateTime  $lastSyncDate
     */
    public function __construct(IResources $resources, \DateTime $lastSyncDate)
    {
        $this->resources = $resources;
        $this->lastSyncDate = $lastSyncDate;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        /** @var ApiContactSuppressionList $contacts */
        $contacts = $this->resources
            ->GetContactsSuppressedSinceDate(
                $this->lastSyncDate->format(\DateTime::ISO8601),
                $take,
                $skip
            );

        return $contacts->toArray();
    }
}
