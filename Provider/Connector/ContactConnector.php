<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

class ContactConnector extends AbstractDotmailerConnector
{
    const TYPE = 'contact';
    const IMPORT_JOB = 'dotmailer_new_contacts';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $channel = $this->getChannel();
        $dateSince = $this->managerRegistry
            ->getRepository('OroCRMDotmailerBundle:Contact')
            ->getLastCreatedAt($channel);

        // if there are imported records at dotMailer
        // and import to OroCRM was not completed - continue from latest we have
        $lastSyncDate = $this->getLastSyncDate();
        if ($dateSince && !$lastSyncDate) {
            // substract some buffer to ensure we got everything
            $dateSince = $dateSince->sub(new \DateInterval('PT2H'));
        }

        return $this->transport->getContacts($dateSince);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.contact.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}