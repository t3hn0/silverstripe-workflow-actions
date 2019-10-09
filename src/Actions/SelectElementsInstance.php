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

    public function updateWorkflowFields($fields)
    {
        parent::updateWorkflowFields($fields);

        $elements = Controller::curr()->currentPage()->ElementalArea()->Elements();

        $options = [];
        foreach ($elements as $e) {
            $options[$e->ID] = $this->listOptionHTML($e->ID, $e->Title, $e->getType());
        }

        $fields->push(
            MultiValueCheckboxField::create('SelectedElements', 'Selected elements', $options)
                ->setDescription(
                    '<em>Selected elements will have their changes published as part of this workflow.'.
                    '<br><br>Save or refresh page to update list.</em>'
                )
                ->addExtraClass('wfa-right-elements-list')
        );
    }

    public function onSaveWorkflowPage($page, $postVars)
    {
        parent::onSaveWorkflowPage($page, $postVars);

        $selected = isset($postVars['SelectedElements']) ? array_keys($postVars['SelectedElements']) : [];
        $this->SelectedElements->setValue($selected);
    }

    protected function listOptionHTML($id, $title, $type)
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