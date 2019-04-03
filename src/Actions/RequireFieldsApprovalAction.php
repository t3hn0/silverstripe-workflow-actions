<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\MultiValueField\Fields\MultiValueTextField;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use SilverStripe\ORM\DataObject;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowTransition;
use SilverStripe\Forms\DropdownField;


/**
 * A workflow action that requires users to populate certain fields
 * before a save will work.
 */
class RequireFieldsApprovalAction extends WorkflowAction
{
    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/approval.png';

    private static $table_name = 'RequireFieldsApprovalAction';

    private static $instance_class = RequireFieldsActionInstance::class;

    private static $db = [
        'RequiredFields'    => 'MultiValueField',
    ];

    private static $has_one = [
        'CancelTransition' => WorkflowTransition::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fieldNames = MultiValueTextField::create('RequiredFields', 'Name of fields that are required before approval');
        $fields->addFieldToTab('Root.Main', $fieldNames);

        $transitions = $this->Transitions()->map();
        if (count($transitions)) {
            $fields->addFieldToTab(
                'Root.Main',
                DropdownField::create('CancelTransitionID', 'Cancel transition: presented as an option if no required fields are populated', $transitions)
            );
        }

        return $fields;

    }

    /**
     *
     */
    public function execute(WorkflowInstance $workflow)
    {
        // Check against the attached object, as well as against
        // the instance itself, for the required fields to be populated
        $action = $workflow->CurrentAction();
        if ($action instanceof RequireFieldsActionInstance) {
            $need = $action->getUnpopulatedFields();
            if (count($need)) {
                return false;
            }
        }

        return true;
    }
}
