<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\ORM\FieldType\DBDatetime;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;

class TimeoutTransitionInstance extends WorkflowActionInstance
{
    private static $table_name = 'TimeoutTransitionInstance';

    public function attemptTimeoutTransition()
    {
        // not timed out?
        if (strtotime('now + 1 hour') < $this->getTimeoutTime()) {
            return 'Timeout date not reached';
        }

        // not curr action?
        $flow = $this->Workflow();
        if (!$flow || $flow->CurrentActionID != $this->ID) {
            return 'Not current action on workflow';
        }

        // transition target no valid?
        $base = $this->BaseAction();
        if (!$base->Transitions()->filter('ID', $base->TimeoutTransitionID)->count()) {
            return 'Transition target not valid';
        }

        // no target?
        $target = $flow->getTarget();
        if (!$target) {
            return 'Workflow target missing';
        }

        // can't edit?
        if (!$target->canEditWorkflow()) {
            return 'Unable to edit target workflow';
        }

        // do transition
        $flow->updateWorkflow([
            'TransitionID' => $base->TimeoutTransitionID,
            'Comment' => 'Automatically transitioned due to timeout'
        ]);

        return false;
    }

    public function getTimeoutTime()
    {
        $base = $this->BaseAction();
        if (!$base->TimeoutTransitionID || !$base->TimeoutCount || !$base->TimeoutIncrement) {
            return false;
        }
        return strtotime("{$this->Created} + {$base->TimeoutCount} {$base->TimeoutIncrement}");
    }

    public function getTimeoutDate($format = 'Y-m-d H:i:s'): string
    {
        if ($time = $this->getTimeoutTime()) {
            return date($format, $time);
        }
        return 'never';
    }

    public function getValidTransitions()
    {
        $valid = parent::getValidTransitions();
        $base = $this->BaseAction();

        foreach ($valid as $tran) {
            if ($tran->ID === $base->TimeoutTransitionID) {
                $date = $this->getTimeoutDate('d/m/y H:i');
                $tran->Title = "{$tran->Title} ({$date})";
                break;
            }
        }

        return $valid;
    }
}
