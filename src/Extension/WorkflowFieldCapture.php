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
        // we know that owner is a Page and that all Pages have this extension
        if ($wfi = $this->owner->getWorkflowInstance()) {
            if ($action = $wfi->CurrentAction()) {
                // incase a page is saved via means other than form submission
                try {
                    $postVars = Controller::curr()->getRequest()->postVars();
                } catch (\Exception $e) {
                    $postVars = [];
                }
                $action->preSaveWorkflowPage();
                $action->onSaveWorkflowPage($this->owner, $postVars);
                $action->postSaveWorkflowPage();
                $action->write();
            }
        }
    }
}
