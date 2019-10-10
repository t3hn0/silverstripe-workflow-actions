<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\ORM\ArrayList;
use Symbiote\AdvancedWorkflow\Actions\PublishItemWorkflowAction;
use Symbiote\AdvancedWorkflow\Actions\SelectElementsInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowEmbargoExpiryExtension;

class PublishWithSelectedElementsActions extends PublishItemWorkflowAction
{
    /**
     * Override of base execute() so that we can publish only selected
     * elements.
     *
     * @param WorkflowInstance $workflow
     * @return bool
     */
    public function execute(WorkflowInstance $workflow)
    {
        if (!$target = $workflow->getTarget()) {
            return true;
        }

        // TODO: Add back queued jobs logic with new job, etc.

        if ($target->hasExtension(WorkflowEmbargoExpiryExtension::class)) {
            $target->AllowEmbargoedEditing = $this->AllowEmbargoedEditing;
            $target->UnPublishOnDate = $target->DesiredUnPublishDate;
            $target->DesiredUnPublishDate = '';

            if ($target->DesiredPublishDate) {
                $target->PublishOnDate = $target->DesiredPublishDate;
                $target->DesiredPublishDate = '';
                $target->write();
            } else {
                $target->write();
                $this->executePublish($target, $workflow);
            }
        } else {
            $this->executePublish($target, $workflow);
        }

        return true;
    }

    /**
     * Publish the target and the selected elements.
     *
     * @param Page $target
     * @param WorkflowInstance $workflow
     * @return void
     */
    public function executePublish($target, $workflow)
    {
        if ($target->hasMethod('publishSingle')) {
            $target->publishSingle();
        }

        try {
            $elements = $target->ElementalArea()->Elements();
            $selected = SelectElementsInstance::findInWorkflow($workflow)->getSelectedElementsIDs();
        } catch (\Exception $e) {
            $elements = new ArrayList();
            $selected = [];
        }

        if ($elements->count() && count($selected)) {
            $filtered = $elements->filter([ 'ID' => $selected ]);
            foreach ($filtered as $ele) {
                if ($ele->hasMethod('publishRecursive')) {
                    $ele->publishRecursive();
                } else if ($ele->hasMethod('publishSingle')) {
                    $ele->publishSingle();
                }
            }
        }
    }
}
