<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;

class DataFieldStrategy extends AddOrReplaceStrategy
{
    const EXISTING_DATAFIELDS_NAMES = 'existingDataFieldsNames';

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if ($entity instanceof $this->entityName) {
            //store imported datafield names in context
            $existingDataFieldsNames = $this->context->getValue(self::EXISTING_DATAFIELDS_NAMES) ?: [];
            $existingDataFieldsNames[] = $entity->getName();
            $this->context->setValue(self::EXISTING_DATAFIELDS_NAMES, $existingDataFieldsNames);
            
            //find existing datafield by name and integration channel
            return $this->databaseHelper->findOneBy(
                $this->entityName,
                ['name' => $entity->getName(), 'channel' => $entity->getChannel()]
            );
        } else {
            return parent::findExistingEntity($entity, $searchContext);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function combineIdentityValues($entity, $entityClass, array $searchContext)
    {
        if ($entity instanceof $this->entityName) {
            $searchContext = ['name' => $entity->getName(), 'channel' => $entity->getChannel()];
        }

        return parent::combineIdentityValues($entity, $entityClass, $searchContext);
    }
}
