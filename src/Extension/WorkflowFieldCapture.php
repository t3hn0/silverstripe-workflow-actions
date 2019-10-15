<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataExtension;

/**
 * Captures workflow fields entered when a user hits
 * "save" instead of a workflow trigger
 */
class WorkflowFieldCapture extends DataExtension
{
    public function onBeforeWrite()
    {
        // get workflow
        if ($this->owner->hasMethod('getWorkflowInstance') && $wfi = $this->owner->getWorkflowInstance()) {
            // get curr action
            if ($action = $wfi->CurrentAction()) {
                // incase this isn't an edit form submission
                try {
                    $postVars = Controller::curr()->getRequest()->postVars();
                } catch (\Exception $e) {
                    $postVars = [];
                }
                $action->invokeWithExtensions('onSaveWorkflowState', $this->owner, $postVars);
                $action->write();
            }
        }
    }
}
