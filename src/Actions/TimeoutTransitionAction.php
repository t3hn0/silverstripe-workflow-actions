<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\FieldType\DBDatetime;

class TimeoutTransitionAction extends WorkflowAction
{
    private static $db = [
        'TimeoutCount' => 'Int',
        'TimeoutIncrement' => 'Enum("Hours,Days,Weeks,Months,Years")',
    ];

    private static $has_one = [
        'TimeoutTransition' => WorkflowTransition::class . '.Action'
    ];

    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/transition.png';
    private static $table_name = 'TimeoutTransitionAction';
    private static $instance_class = TimeoutTransitionInstance::class;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // increments
        $vals = $this->dbObject('TimeoutIncrement')->enumValues();
        $increments = array_combine($vals, $vals);

        // transitions
        $transitions = [ 0 => 'None' ];
        foreach ($this->Transitions()->toArray() as $t) {
            $transitions[$t->ID] = $t->Title.' ('.$t->NextAction->Title.')';
        }

        $fields->addFieldsToTab('Root.Main', [
            FieldGroup::create('Timeout', [
                new NumericField('TimeoutCount', 'Wait for this long:'),
                new DropdownField('TimeoutIncrement', '', $increments),
                new LiteralField('', '<span style="font-size:30px;margin-right:8px">&rarr;</span>'),
                new DropdownField('TimeoutTransitionID', 'And then perform transition:', $transitions),
            ])
        ]);

        return $fields;
    }

    public function execute(WorkflowInstance $workflow)
    {
        // we can only be transitioned manually or by WorkflowTimeoutJob
        return false;
    }
}
