<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;

class WorkflowActionInstanceExtension extends DataExtension
{
    /**
     * Hook into object save event
     *
     * @see WorkflowFieldCapture
     *
     * @param DataObject $object
     * @param array $postVars
     * @return void
     */
    public function onSaveWorkflowState($object, $postVars)
    {
        if (isset($postVars['Comment'])) {
            $this->getOwner()->Comment = $postVars['Comment'];
        }
    }
}