<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;

/**
 * Captures workflow fields entered when a user hits
 * "save" instead of a workflow trigger
 */
class WorkflowFieldCapture extends DataExtension
{
    public function onBeforeWrite()
    {
        if ($this->owner->Comment) {
            // see if we've got an active workflow that might be interested in the
            // comment text
            $active = $this->owner->getWorkflowInstance();
            if ($active) {
                $action = $active->CurrentAction();
                if ($action) {
                    $action->update($this->owner->toMap());
                    $action->write();
                }
            }
        }
    }
}
