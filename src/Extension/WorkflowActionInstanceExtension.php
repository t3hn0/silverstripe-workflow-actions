<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;

class WorkflowActionInstanceExtension extends DataExtension
{
    private $brokenOnSaveWorkflowPage = false;

    /**
     * Override this in descendants of `WorkflowActionInstance`
     *
     * @param Page $page The page object being saved
     * @param array $postVars The submitted fields/values
     * @return void
     */
    public function onSaveWorkflowPage($page, $postVars)
    {
        $this->brokenOnSaveWorkflowPage = false;

        if (isset($postVars['Comment'])) {
            $this->getOwner()->Comment = $postVars['Comment'];
        }
    }

    public function preSaveWorkflowPage()
    {
        $this->brokenOnSaveWorkflowPage = true;
    }

    public function postSaveWorkflowPage()
    {
        if ($this->brokenOnSaveWorkflowPage) {
            user_error(static::class . " has a broken onSaveWorkflowPage() function."
                . " Make sure that you call parent::onSaveWorkflowPage().", E_USER_ERROR);
        }
    }
}