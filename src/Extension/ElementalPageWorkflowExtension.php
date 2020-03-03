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
        $owner = $this->getOwner();
        if ($owner->ModifiedElements) {
            $vals = $owner->ModifiedElements->getValues() ?: [];

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
    }

    public function elementModified($element) {
        $owner = $this->getOwner();
        if ($owner->ModifiedElements) {
            $vals = $owner->ModifiedElements->getValues() ?: [];

            $changes = $element->getChangedFields(true, DataObject::CHANGE_VALUE);
            if (!count($changes)) {
                return;
            }

            $vals[$element->ID] = json_encode($changes);
            $owner->ModifiedElements->setValue($vals);
        }
    }

    public function onBeforePublish() {
        $owner = $this->getOwner();
        if ($owner->ModifiedElements) {
            $owner->ModifiedElements->setValue([]);
        }
    }
}
