<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRM\Bundle\DotmailerBundle\ImportExport\JobContextComposite;

abstract class AbstractReader extends IteratorBasedReader
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ContextInterface
     */
    protected $jobContext;

    /**
     * @var ConnectorContextMediator
     */
    protected $contextMediator;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param ConnectorContextMediator $contextMediator
     * @param ManagerRegistry          $managerRegistry
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $contextMediator,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($contextRegistry);
        $this->contextMediator = $contextMediator;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->jobContext = new JobContextComposite($stepExecution, $this->contextRegistry);
        parent::setStepExecution($stepExecution);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->context = $context;
        $this->initializeReader();
    }

    abstract protected function initializeReader();

    /**
     * @return Channel
     */
    protected function getChannel()
    {
        return $this->contextMediator->getChannel($this->context);
    }
}
