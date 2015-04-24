<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class ContactIterator extends AbstractIterator
{
    /** @var int */
    protected $batchSize = 100;

    /** @var IResources */
    protected $resources;

    /** @var \DateTime|null */
    protected $dateSince;

    /**
     * @param IResources $resources
     * @param \DateTime  $dateSince
     */
    public function __construct(IResources $resources, $dateSince = null)
    {
        $this->resources = $resources;
        $this->dateSince = $dateSince;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($select, $skip)
    {
        if (is_null($this->dateSince)) {
            $items = $this->resources->GetContacts(false, $select, $skip);
        } else {
            $items = $this->resources->GetContactsModifiedSinceDate(
                $this->dateSince->format('Y-m-dTH:i:sZ'),
                true,
                $select,
                $skip
            );
        }

        return $items->toArray();
    }
}
