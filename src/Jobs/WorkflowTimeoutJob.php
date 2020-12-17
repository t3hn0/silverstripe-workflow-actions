<?php

namespace Symbiote\AdvancedWorkflow\Jobs;

use Symbiote\AdvancedWorkflow\Actions\TimeoutTransitionInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

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
        $curIds = WorkflowInstance::get()->filter('CurrentActionID:GreaterThan', 0)->column('CurrentActionID');
        $where = '"ID" IN (' . implode(',', $curIds) . ')';
        $ttis = TimeoutTransitionInstance::get()->where($where);
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
                $err = '';
                if ($tti->attemptTimeoutTransition($err)) {
                    $this->addMessage("Successfully transitioned item #{$id}.");
                } else {
                    $this->addMessage("Skipped item #{$id} due to '{$err}'.");
                }
            }
            $this->currentStep++;
        }

        if ($this->currentStep >= $this->totalSteps) {
            $this->addMessage("Complete.");
            $this->isComplete = true;
        }
    }

    public function afterComplete()
    {
        $queueService = singleton(QueuedJobService::class);
        $defaults = $queueService->defaultJobs;

        if (isset($defaults['WorkflowTimeoutJob']['startTimeString'])) {
            $time = strtotime($defaults['WorkflowTimeoutJob']['startTimeString']);
            $queueService->queueJob(new WorkflowTimeoutJob(), date('Y-m-d H:i:s', $time));
        }
    }
}
