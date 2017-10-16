<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;

class ExportContactsStatusUpdateProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldSubscribeOnExportContactsStatusUpdateTopic()
    {
        $this->assertEquals(
            [Topics::EXPORT_CONTACTS_STATUS_UPDATE],
            ExportContactsStatusUpdateProcessor::getSubscribedTopics()
        );
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldLogAndRejectIfMessageBodyMissIntegrationId()
    {
        $message = new NullMessage();
        $message->setBody('[]');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The message invalid. It must have integrationId set', ['message' => $message])
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The malformed json given.
     */
    public function testThrowIfMessageBodyInvalidJson()
    {
        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody('[}');

        $processor->process($message, new NullSession());
    }

    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration not found: theIntegrationId', ['message' => $message])
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger
        );


        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration is not enabled: theIntegrationId', ['message' => $message])
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldDoNothingIfExportFinishedAndErrorsProcessed()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinishedForAddressBook')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessedForAddressBook')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('updateExportResultsForAddressBook')
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('processExportFaultsForAddressBook')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldUpdateExportResultsIfExportIsNotFinished()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinishedForAddressBook')
            ->willReturn(false)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('isExportFaultsProcessedForAddressBook')
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('updateExportResultsForAddressBook')
            ->with(self::identicalTo($integration))
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('processExportFaultsForAddressBook')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldProcessExportFaultsIfExportFinished()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinishedForAddressBook')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessedForAddressBook')
            ->willReturn(false)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('updateExportResultsForAddressBook')
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('processExportFaultsForAddressBook')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRunExportAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $jobRunner = new JobRunner();

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $jobRunner,
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:export_contacts_status_update:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock)
        ;

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($entityManager = null)
    {
        $helperMock = $this->createMock(DoctrineHelper::class);
        $helperMock
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager)
        ;

        $repository = $this->getMockBuilder(
            'Oro\Bundle\DotmailerBundle\Entity\Repository\AddressBook'
        )
            ->setMethods(['getConnectedAddressBooks'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('getConnectedAddressBooks')
            ->willReturn(
                [new AddressBook()]
            );

        $helperMock
            ->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository)
        ;

        return $helperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportManager
     */
    private function createExportManagerMock()
    {
        return $this->createMock(ExportManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
