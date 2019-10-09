<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Security\Group;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\Extension\ContentApproversExtension;

class SelectElementsAction extends WorkflowAction
{
    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/assign.png';

    private static $table_name = 'SelectElementsAction';

    private static $instance_class = SelectElementsInstance::class;

    private static $db = [];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }

    public function execute(WorkflowInstance $workflow)
    {
        $target = $workflow->getTarget();

        return true;
    }
}
