<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;

class SelectElementsAction extends WorkflowAction
{
    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/assign.png';

    private static $table_name = 'SelectElementsAction';

    private static $instance_class = SelectElementsInstance::class;

    private static $db = [];

    public function getCMSFields()
    {
        return parent::getCMSFields();
    }

    public function execute(WorkflowInstance $workflow)
    {
        // see SelectElementsInstance::onSaveWorkflowState()
        return true;
    }
}
