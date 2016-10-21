<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Command;

use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DotmailerBundle\Command\ContactsExportStatusUpdateCommand;

/**
 * @dbIsolation
 */
class ContactsExportStatusUpdateCommandTest extends WebTestCase
{
    /**
     * @var ExportManager
     */
    protected $exportManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $exportManagerMock;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            ]
        );

        $this->exportManager = $this->getContainer()
            ->get(ContactsExportStatusUpdateCommand::EXPORT_MANAGER);
        $this->exportManagerMock = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Model\ExportManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getContainer()
            ->set(ContactsExportStatusUpdateCommand::EXPORT_MANAGER, $this->exportManagerMock);

        $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
    }

    protected function tearDown()
    {
        $this->getContainer()
            ->set(ContactsExportStatusUpdateCommand::EXPORT_MANAGER, $this->exportManager);

        // clear DB from separate connection, close to avoid connection limit and memory leak
        $manager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $manager->rollback();
        $manager->getConnection()->close();

        parent::tearDown();
    }

    public function testExecute()
    {
        $notExportedChannel = $this->getReference('oro_dotmailer.channel.third');
        $secondNotExportedChannel = $this->getReference('oro_dotmailer.channel.fourth');

        $exportedChannel = $this->getReference('oro_dotmailer.channel.first');
        $secondExportedChannel = $this->getReference('oro_dotmailer.channel.second');

        $this->exportManagerMock
            ->expects($this->any())
            ->method('isExportFinished')
            ->will(
                $this->returnValueMap(
                    [
                        [$exportedChannel, true],
                        [$secondExportedChannel, true],
                        [$notExportedChannel, false],
                        [$secondNotExportedChannel, false],
                    ]
                )
            );

        $this->exportManagerMock
            ->expects($this->exactly(2))
            ->method('updateExportResults')
            ->withConsecutive([$notExportedChannel], [$secondNotExportedChannel]);

        $this->exportManagerMock
            ->expects($this->exactly(2))
            ->method('processExportFaults')
            ->withConsecutive([$exportedChannel], [$secondExportedChannel]);

        $this->runCommand(ContactsExportStatusUpdateCommand::NAME, ['--verbose' => true]);
    }
}
