<?php

/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

namespace WalleePayment\Components\ArrayBuilder;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wallee\Sdk\Model\LabelDescriptorGroup;

class LabelGroup extends AbstractArrayBuilder
{
    /**
     *
     * @var LabelDescriptorGroup
     */
    private $group;

    /**
     *
     * @var Label[]
     */
    private $labels;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param LabelDescriptorGroup $group
     * @param Label[] $labels
     */
    public function __construct(ContainerInterface $container, LabelDescriptorGroup $group, array $labels)
    {
        parent::__construct($container);
        $this->group = $group;
        $this->labels = $labels;
    }

    public function build()
    {
        usort($this->labels, function ($a, $b) {
            return $a->getDescriptor()->getWeight() - $b->getDescriptor()->getWeight();
        });
        $labels = [];
        foreach ($this->labels as $label) {
            $labels[] = $label->build();
        }

        return [
            'group' => [
                'id' => $this->group->getId(),
                'name' => $this->translate($this->group->getName()),
                'description' => $this->translate($this->group->getDescription()),
                'weight' => $this->group->getWeight()
            ],
            'labels' => $labels
        ];
    }

    /**
     *
     * @return \Wallee\Sdk\Model\LabelDescriptorGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     *
     * @param ContainerInterface $container
     * @param Label[] $labels
     * @return array
     */
    public static function buildGrouped(ContainerInterface $container, array $labels)
    {
        /** @var \WalleePayment\Components\Provider\LabelDescriptorGroup $labelDescriptorGroupProvider */
        $labelDescriptorGroupProvider = $container->get('wallee_payment.provider.label_descriptor_group');

        $result = [];
        try {
            $labelsByGroupId = array();
            foreach ($labels as $label) {
                $labelsByGroupId[$label->getDescriptor()->getGroup()][] = $label;
            }

            $groups = array();
            foreach ($labelsByGroupId as $groupId => $labels) {
                $group = $labelDescriptorGroupProvider->find($groupId);
                if ($group) {
                    $groups[] = new LabelGroup($container, $group, $labels);
                }
            }

            usort($groups, function ($a, $b) {
                return $a->getGroup()->getWeight() - $b->getGroup()->getWeight();
            });


            foreach ($groups as $group) {
                $result[] = $group->build();
            }
        } catch (\Exception $e) {
            // If label descriptors and label descriptor groups cannot be loaded from wallee, the labels cannot be displayed.
        }
        return $result;
    }
}
