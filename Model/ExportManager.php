<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBookContactsExportRepository;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;

class ExportManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var DotmailerTransport
     */
    protected $dotmailerTransport;

    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $addressBookContactClassName;

    /**
     * @param ManagerRegistry    $managerRegistry
     * @param DotmailerTransport $dotmailerTransport
     * @param Executor           $executor
     * @param string             $addressBookContactClassName
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        DotmailerTransport $dotmailerTransport,
        Executor $executor,
        $addressBookContactClassName
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->dotmailerTransport = $dotmailerTransport;
        $this->executor = $executor;
        $this->addressBookContactClassName = $addressBookContactClassName;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     *
     * @deprecated
     * @see isExportFinishedForAddressBook
     */
    public function isExportFinished(Channel $channel)
    {
        return $this->getAddressBookContactsExportRepository()->isExportFinished($channel);
    }

    /**
     * @param Channel $channel
     * @param null|AddressBook $addressBook
     *
     * @return bool
     */
    public function isExportFinishedForAddressBook(Channel $channel, AddressBook $addressBook)
    {
        return $this->getAddressBookContactsExportRepository()->isExportFinishedForAddressBook($channel, $addressBook);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     *
     * @deprecated
     * @see isExportFaultsProcessedForAddressBook
     */
    public function isExportFaultsProcessed(Channel $channel)
    {
        return $this->getAddressBookContactsExportRepository()->isExportFaultsProcessed($channel);
    }

    /**
     * @param Channel $channel
     * @param null|AddressBook $addressBook
     *
     * @return bool
     */
    public function isExportFaultsProcessedForAddressBook(Channel $channel, AddressBook $addressBook)
    {
        return $this->getAddressBookContactsExportRepository()
            ->isExportFaultsProcessedForAddressBook($channel, $addressBook);
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     *
     * @deprecated
     * @see updateExportResultsForAddressBook
     */
    public function updateExportResults(Channel $channel)
    {
        $exportRepository = $this->getAddressBookContactsExportRepository();
        $this->dotmailerTransport->init($channel->getTransport());
        $isExportFinished = true;
        foreach ($exportRepository->getNotFinishedExports($channel) as $export) {
            $dotmailerStatus = $this->dotmailerTransport->getImportStatus($export->getImportId());
            $exportStatus = $exportRepository->getStatus($dotmailerStatus->status);
            $export->setStatus($exportStatus);
            if ($exportRepository->isNotFinishedStatus($exportStatus)) {
                $isExportFinished = false;
            }
        }
        $this->managerRegistry->getManager()->flush();
        if ($isExportFinished) {
            $this->processExportFaults($channel);
        }
        return $isExportFinished;
    }

    /**
     * @param Channel $channel
     * @param AddressBook $addressBook
     *
     * @return bool
     */
    public function updateExportResultsForAddressBook(Channel $channel, AddressBook $addressBook)
    {
        $exportRepository = $this->getAddressBookContactsExportRepository();

        $this->dotmailerTransport->init($channel->getTransport());

        $isExportFinished = true;

        foreach ($exportRepository->getNotFinishedExportsForAddressBook($channel, $addressBook) as $export) {
            $dotmailerStatus = $this->dotmailerTransport->getImportStatus($export->getImportId());
            $exportStatus = $exportRepository->getStatus($dotmailerStatus->status);

            $export->setStatus($exportStatus);
            if ($exportRepository->isNotFinishedStatus($exportStatus)) {
                $isExportFinished = false;
            }
        }

        $this->managerRegistry->getManager()->flush();

        if ($isExportFinished) {
            $this->processExportFaultsForAddressBook($channel, $addressBook);
        }

        return $isExportFinished;
    }

    /**
     * @param Channel $channel
     *
     * @deprecated
     * @see processExportFaultsForAddressBook
     */
    public function processExportFaults(Channel $channel)
    {
        $jobResult = $this->startUpdateSkippedContactsStatusJob($channel);
        if (!$jobResult) {
            throw new RuntimeException('Update skipped contacts failed.');
        }
        $this->updateAddressBooksSyncStatus($channel);
        $this->managerRegistry->getManager()->flush();
    }

    /**
     * @param Channel $channel
     * @param AddressBook $addressBook
     */
    public function processExportFaultsForAddressBook(Channel $channel, AddressBook $addressBook)
    {
        $jobResult = $this->startUpdateSkippedContactsStatusJob($channel);
        if (!$jobResult) {
            throw new RuntimeException('Update skipped contacts failed.');
        }

        $this->updateAddressBooksSyncStatuses([$addressBook]);

        $this->managerRegistry->getManager()->flush();
    }

    /**
     * @param Channel $channel
     */
    public function updateAddressBooksSyncStatus(Channel $channel)
    {
        $addressBooks = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->findBy(['channel' => $channel]);

        $this->updateAddressBooksSyncStatuses($addressBooks);
    }

    /**
     * @param AddressBook[] $addressBooks
     */
    public function updateAddressBooksSyncStatuses(array $addressBooks)
    {
        $exportRepository = $this->getAddressBookContactsExportRepository();

        $lastFinishedStatus = null;
        foreach ($addressBooks as $addressBook) {
            $addressBookExports = $exportRepository->getExportsByAddressBook($addressBook);

            $isExportFinished = true;
            $lastErrorStatus = null;
            foreach ($addressBookExports as $addressBookExport) {
                $status = $addressBookExport->getStatus();

                if ($exportRepository->isErrorStatus($status) && !$lastErrorStatus) {
                    $lastErrorStatus = $status;
                }

                $isExportFinished = $isExportFinished && $exportRepository->isFinishedStatus($status);
            }

            if ($isExportFinished) {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getFinishedStatus(), true);
            } elseif ($lastErrorStatus) {
                $this->updateAddressBookSyncStatus($addressBook, $lastErrorStatus, true);
            } else {
                $this->updateAddressBookSyncStatus($addressBook, $exportRepository->getNotFinishedStatus(), false);
            }
        }
    }

    /**
     * @param AddressBook       $addressBook
     * @param AbstractEnumValue $status
     * @param bool              $updateLastExportedAt
     */
    protected function updateAddressBookSyncStatus(
        AddressBook $addressBook,
        AbstractEnumValue $status,
        $updateLastExportedAt
    ) {
        $addressBook->setSyncStatus($status);
        if ($updateLastExportedAt) {
            $addressBook->setLastExportedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }

    /**
     * @param Channel $channel
     *
     * @return JobResult
     */
    protected function startUpdateSkippedContactsStatusJob(Channel $channel)
    {
        $configuration = [
            ProcessorRegistry::TYPE_IMPORT => [
                'entityName'     => $this->addressBookContactClassName,
                'channel'        => $channel->getId(),
                'channelType'    => $channel->getType(),
            ],
        ];

        return $this->executor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            'dotmailer_import_not_exported_contact',
            $configuration
        );
    }

    /**
     * @return AddressBookContactsExportRepository
     */
    private function getAddressBookContactsExportRepository()
    {
        return $this->managerRegistry->getRepository(AddressBookContactsExport::class);
    }
}
