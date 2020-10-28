<?php

namespace Symbiote\AdvancedWorkflow\Jobs;

use Symbiote\AdvancedWorkflow\Actions\TimeoutTransitionInstance;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

class WorkflowTimeoutJob extends AbstractQueuedJob
{
    public static $limit = 20;

    public function getTitle()
    {
        return "Workflow Timeout Job";
    }

    public function getSignature()
    {
        return WorkflowTimeoutJob::class;
    }

    public function setup()
    {
        $ttis = TimeoutTransitionInstance::get();
        $this->totalSteps = $ttis->count();
        $this->ids = $ttis->column('ID');
        $this->offset = 0;
        $this->addMessage("Processing {$this->totalSteps} TimeoutTransitionInstance items.");
    }

    public function process()
    {
        // get next segment of ids
        $id_seg = array_slice($this->ids, $this->offset, static::$limit);
        $this->offset += static::$limit;

        foreach ($id_seg as $id) {
            // get workflow inst
            if ($tti = TimeoutTransitionInstance::get_by_id($id)) {
                // attemp transition
                if ($err = $tti->attemptTimeoutTransition()) {
                    $this->addMessage("Skipped item #{$id} due to '{$err}'.");
                } else {
                    $this->addMessage("Successfully transitioned item #{$id}.");
                }
            }
            $this->currentStep++;
        }

        if ($this->currentStep >= $this->totalSteps) {
            $this->addMessage("Complete.");
            $this->isComplete = true;
        }
    }
}
