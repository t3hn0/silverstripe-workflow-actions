<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Controller;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;
use Symbiote\MultiValueField\Fields\MultiValueCheckboxField;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;

class SelectElementsInstance extends WorkflowActionInstance
{
    private static $db = [
        'SelectedElements' => MultiValueField::class
    ];

    private static $table_name = 'SelectElementsInstance';

    public function onSaveWorkflowPage($page, $postVars)
    {
        parent::onSaveWorkflowPage($page, $postVars);

        $selected = isset($postVars['SelectedElements']) ? $postVars['SelectedElements'] : [];
        $this->SelectedElements->setValue($selected);
    }

    public function updateWorkflowFields($fields)
    {
        parent::updateWorkflowFields($fields);

        $fields->push($this->getSelectionList());
    }

    public function getSelectionList($readonly = false)
    {
        try {
            $elements = Controller::curr()->currentPage()->ElementalArea()->Elements();
        } catch (\Exception $e) {
            $elements = [];
        }

        $options = [];
        foreach ($elements as $e) {
            $options[$e->ID] = $this->makeOptionHTML($e->ID, $e->Title, $e->getType());
        }

        $checklist = MultiValueCheckboxField::create('SelectedElements', 'Selected elements', $options)
            ->setDescription(
                '<p><em>Selected elements will have their changes published as part of this workflow.</em></p>'.
                '<p><em>Save or refresh page to update list.</em></p>'
            )
            ->addExtraClass('wfa-right-elements-list');

        if ($readonly) {
            $checklist->addExtraClass('wfa-readonly');
            $checklist->setDisabled(true);
            $checklist->setReadonly(true);
            $checklist->setValue($this->SelectedElements->getValue());
        }

        return $checklist;
    }

    public static function findInWorkflow($wfInstance)
    {
        return $wfInstance->Actions()
            ->filter([ 'ClassName' => SelectElementsInstance::class ])
            ->sort('Created DESC')
            ->first();
    }

    protected function makeOptionHTML($id, $title, $type)
    {
        // label
        $label = $title ?: '<em>untitled</em>';
        $html = "<div class=\"wfa-trunc\">{$label}</div>";
        // tooltip
        $tip = $type ?: 'no type';
        $html .= "<div class=\"wfa-tip\">#{$id} <strong>{$label}</strong><br><em>&lt;{$tip}&gt;</em></div>";

        return $html;
    }
}