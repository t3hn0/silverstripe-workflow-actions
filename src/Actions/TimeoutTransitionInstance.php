<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;

class TimeoutTransitionInstance extends WorkflowActionInstance
{
    private static $table_name = 'TimeoutTransitionInstance';

    public function attemptTimeoutTransition(string &$err = ''): bool
    {
        // not timed out?
        if (strtotime('now') < $this->getTimeoutTime()) {
            $err = 'Timeout date not reached';
            return false;
        }

        // not curr action?
        $flow = $this->Workflow();
        if (!$flow || $flow->CurrentActionID != $this->ID) {
            $err = 'Not current action on workflow';
            return false;
        }

        // transition target no valid?
        $base = $this->BaseAction();
        if (!$base->Transitions()->filter('ID', $base->TimeoutTransitionID)->count()) {
            $err = 'Transition target not valid';
            return false;
        }

        // no target?
        $target = $flow->getTarget();
        if (!$target) {
            $err = 'Workflow target missing';
            return false;
        }

        // can't edit?
        if (!$target->canEditWorkflow()) {
            $err = 'Unable to edit target workflow';
            return false;
        }

        // do transition
        $flow->updateWorkflow([
            'TransitionID' => $base->TimeoutTransitionID,
            'Comment' => 'Automatically transitioned due to timeout'
        ]);
        return true;
    }

    public function getTimeoutTime()
    {
        $base = $this->BaseAction();
        $flow = $this->Workflow();

        // need transition target
        if (!$flow || !$base || !$base->TimeoutTransitionID) {
            return false;
        }

        // static period
        if ($base->TimeoutType == TimeoutTransitionAction::STATIC_PERIOD && $base->TimeoutCount && $base->TimeoutIncrement) {
            return strtotime("{$this->Created} + {$base->TimeoutCount} {$base->TimeoutIncrement}");
        }
        // date field
        else if ($base->TimeoutType == TimeoutTransitionAction::DATE_FIELD && $base->TimeoutDateField && $flow->TargetID) {
            $target = $flow->getTarget();
            if ($target->hasField($base->TimeoutDateField)) {
                return strtotime($target->{$base->TimeoutDateField});
            }
        }

        return false;
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
