<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class TimeoutTransitionAction extends SetPropertyWorkflowAction
{
    private static $db = [
        'TimeoutType' => 'Enum("Static Period,Date Field","Static Period")',
        'TimeoutCount' => 'Int',
        'TimeoutIncrement' => 'Enum("Hours,Days,Weeks,Months,Years")',
        'TimeoutDateField' => 'Varchar(64)',
    ];

    private static $has_one = [
        'TimeoutTransition' => WorkflowTransition::class . '.Action'
    ];

    private static $icon = 'symbiote/silverstripe-advancedworkflow:images/transition.png';
    private static $table_name = 'TimeoutTransitionAction';
    private static $instance_class = TimeoutTransitionInstance::class;

    public const STATIC_PERIOD = 'Static Period';
    public const DATE_FIELD = 'Date Field';

    public function __construct($record = null, $isSingleton = false, $queryParams = array())
    {
        parent::__construct($record, $isSingleton, $queryParams);
        Requirements::customCSS('.tta .form__fieldgroup-item { width: 20%; }');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // increments
        $vals = $this->dbObject('TimeoutType')->enumValues();
        $types = array_combine($vals, $vals);

        // increments
        $vals = $this->dbObject('TimeoutIncrement')->enumValues();
        $increments = array_combine($vals, $vals);

        // transitions
        $transitions = [ 0 => 'None' ];
        foreach ($this->Transitions()->toArray() as $t) {
            $transitions[$t->ID] = $t->Title.' ('.$t->NextAction->Title.')';
        }

        $groupFields = [
            DropdownField::create('TimeoutType', 'Timeout Type', $types)
        ];

        if ($this->TimeoutType == static::STATIC_PERIOD) {
            $groupFields[] = NumericField::create('TimeoutCount', 'Wait for this long:');
            $groupFields[] = DropdownField::create('TimeoutIncrement', '', $increments);
        } else if ($this->TimeoutType == static::DATE_FIELD) {
            $groupFields[] = TextField::create('TimeoutDateField', 'Name of date field on target');
        }

        $groupFields[] = LiteralField::create('Then', '<span style="font-size:30px;margin-right:8px">&rarr;</span>');
        $groupFields[] = DropdownField::create('TimeoutTransitionID', 'And then perform transition:', $transitions);

        $fields->insertBefore('Property', FieldGroup::create('Timeout', $groupFields)->addExtraClass('tta'));

        return $fields;
    }

    public function execute(WorkflowInstance $workflow)
    {
        parent::execute($workflow);
        return true;
    }
}
