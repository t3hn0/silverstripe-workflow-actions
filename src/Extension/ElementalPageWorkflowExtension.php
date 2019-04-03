<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use Symbiote\MultiValueField\Fields\MultiValueTextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\Parsers\Diff;

class ElementalPageWorkflowExtension extends DataExtension {
    private static $db = [
        'ModifiedElements' => 'MultiValueField',
    ];

    public function updateCMSFields(FieldList $fields) {
        $vals = $this->owner->ModifiedElements->getValues();
        $vals = $vals ?: [];

        $sourceKeys = array_keys($vals);
        $sourceVals = array_values($vals);

        foreach ($vals as $elemId => $changes) {
            $change = @json_decode($changes, true);
            if (count($change)) {
                $fields->addFieldToTab('Root.Changes', LiteralField::create($elemId.'_change', "<strong>$elemId</strong>"));
                foreach ($change as $field => $values) {
                    $diff = Diff::compareHTML($values['before'], $values['after']);
                    $fieldValue = '<div class="workflow-field-diff">' . $field . ': ' . $diff . '</div>';
                    $fields->addFieldToTab('Root.Changes', LiteralField::create($field.'_change', $fieldValue));
                }
            }
        }
    }

    public function elementModified($element) {
        $vals = $this->owner->ModifiedElements->getValues();
        $vals = $vals ?: [];

        $changes = $element->getChangedFields(true, DataObject::CHANGE_VALUE);
        if (!count($changes)) {
            return;
        }


        $vals[$element->ID] = json_encode($changes);
        $this->owner->ModifiedElements = $vals;
    }

    public function onBeforePublish() {
        $this->owner->ModifiedElements = [];
    }
}
