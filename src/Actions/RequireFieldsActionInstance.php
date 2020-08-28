<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;
use SilverStripe\ORM\ArrayList;

class RequireFieldsActionInstance extends WorkflowActionInstance
{
    private static $table_name = 'RequireFieldsActionInstance';

    public function getValidTransitions()
    {
        $transitions = parent::getValidTransitions();

        $need = $this->getUnpopulatedFields();

        if (count($need)) {
            $list = ArrayList::create();

            $cancel = $this->BaseAction()->CancelTransition();
            if ($cancel && $cancel->ID) {
                $list->push($cancel);
            }

            return $list;
        }
        return $transitions;
    }

    /**
     * @return array
     */
    public function getUnpopulatedFields() {
        $req = $this->BaseAction()->RequiredFields;
        if ($req) {
            $req = $req->getValues();
            if (!$req || !count($req)) {
                return [];
            }

            $target = $this->Workflow()->getTarget();
            if (!$target) {
                return [];
            }

            $need = array_combine($req, $req);

            // let's look at whether the fields / relationships are populated
            foreach ($req as $field) {
                if ($target->$field) {
                    unset($need[$field]);
                    continue;
                }
                if ($target->hasValue($field)) {
                    unset($need[$field]);
                    continue;
                }
                if ($this->hasValue($field)) {
                    unset($need[$field]);
                    continue;
                }
            }

            return array_values($need);
        }
        return [];
    }
}
