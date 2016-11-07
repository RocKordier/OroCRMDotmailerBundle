<?php

namespace Oro\Bundle\DotmailerBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DotmailerBundle\Entity\DataFieldMapping;

class DataFieldMappingFormSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * Collect mapping data and update mapping config source element
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data === null) {
            return;
        }

        /** @var $data DataFieldMapping */
        if ($data->getConfigs()) {
            $configs = $data->getConfigs();
            $mappings = [];
            foreach ($configs as $config) {
                $mapping = [];
                $mapping['entityFields'] = $config->getEntityFields();
                $mapping['dataField'] = [
                    'value' => $config->getDataField()->getId(),
                    'name' => $config->getDataField()->getName()
                ];
                $mapping['isTwoWaySync'] = $config->isIsTwoWaySync();
                $mappings[] = $mapping;
            }
            $mappings = ['mapping' => $mappings];
            $form->get('config_source')->setData(json_encode($mappings));
        }
    }

    /**
     * Process submitted mapping data and add to mapping collection form
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!empty($data['config_source'])) {
            $mappingConfigurations = json_decode($data['config_source'], true);
            if ($mappingConfigurations) {
                foreach ($mappingConfigurations['mapping'] as $mappingConfiguration) {
                    if (isset($mappingConfiguration['dataField']['value'])) {
                        $mappingConfiguration['dataField'] = $mappingConfiguration['dataField']['value'];
                    }
                    $mappingConfiguration = $this->processTwoWaySync($mappingConfiguration);
                    $data['configs'][] = $mappingConfiguration;
                }
                $event->setData($data);
            }
        }
    }

    /**
     * Check if two way sync can be applied to the mapping, remove it from data if it can't
     *
     * @param array $mappingConfiguration
     *
     * @return array
     */
    protected function processTwoWaySync($mappingConfiguration)
    {
        $unset = false;
        if (!isset($mappingConfiguration['isTwoWaySync']) || !$mappingConfiguration['isTwoWaySync']) {
            $unset = true;
        }
        $entityFields = explode(',', $mappingConfiguration['entityFields']);
        //Two way sync should be disabled if we have more than 1 field chosen
        if (count($entityFields) > 1) {
            $unset = true;
        } else {
            $field = current($entityFields);
            //if relation field is used
            if (strrpos($field, '+') !== false) {
                $unset = true;
            }
        }
        if ($unset) {
            unset($mappingConfiguration['isTwoWaySync']);
        }

        return $mappingConfiguration;
    }
}
