<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Security\Group;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\Extension\ContentApproversExtension;

class AssignContentApproversAction extends WorkflowAction
{
    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/assign.png';

    private static $table_name = 'AssignContentApproversAction';

    private static $db = [
        'GroupType' => 'Varchar',
        'AssignInitiator' => 'Boolean',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $types = array('approver' => 'Approver', 'publisher' => 'Publisher');
        $fields->addFieldsToTab('Root.Main', array(
            new DropdownField('GroupType', 'Type of user group to assign', $types),
            new CheckboxField('AssignInitiator', $this->fieldLabel('AssignInitiator')),
        ));

        return $fields;
    }

    public function execute(WorkflowInstance $workflow)
    {
        $appliedTo = null;
        $target = $workflow->getTarget();

        if ($target && $target->hasExtension(ContentApproversExtension::class)) {
            $user = null;
            $group = null;
            switch ($this->GroupType) {
                case 'publisher':
                    $group = $target->getPublisher();
                    break;
                default:
                    $group = $target->getApprover();
                    break;
            }

            if ($group) {
                $workflow->Users()->removeAll();
                $workflow->Groups()->removeAll();

                $workflow->Groups()->add($group);
            }
        }

        if ($this->AssignInitiator) {
            $workflow->Users()->add($workflow->Initiator());
        }

        // if we don't find any approvers we just return true so that we can approve some other way?
        return true;
    }

}
