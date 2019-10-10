<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\MultiValueField\Fields\MultiValueCheckboxField;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;

class SelectElementsInstance extends WorkflowActionInstance
{
    private static $db = [
        'SelectedElements' => MultiValueField::class
    ];

    private static $table_name = 'SelectElementsInstance';

    /**
     * Hook into page save event
     *
     * @see WorkflowFieldCapture
     *
     * @param Page $page
     * @param array $postVars
     * @return void
     */
    public function onSaveWorkflowPage($page, $postVars)
    {
        parent::onSaveWorkflowPage($page, $postVars);

        // store selected elements
        $selected = isset($postVars['SelectedElements']) ? $postVars['SelectedElements'] : [];
        $this->SelectedElements->setValue($selected);
    }

    /**
     * Add fields to right sidebar
     *
     * @param FieldList $fields
     * @return void
     */
    public function updateWorkflowFields($fields)
    {
        parent::updateWorkflowFields($fields);

        $fields->push($this->getSelectedElementsField());
    }

    /**
     * Creates MultiValueCheckboxField for right sidebar
     *
     * @param boolean $readonly
     * @return MultiValueCheckboxField
     */
    public function getSelectedElementsField($readonly = false)
    {
        // get page elements
        try {
            $elements = Controller::curr()->currentPage()->ElementalArea()->Elements();
        } catch (\Exception $e) {
            $elements = [];
        }

        // form a selection for element
        $options = [];
        foreach ($elements as $e) {
            $options[$e->ID] = $this->makeOptionHTML($e->ID, $e->Title, $e->getType());
        }

        // make field
        $checklist = MultiValueCheckboxField::create('SelectedElements', 'Selected elements', $options)
            ->setDescription(
                '<p><em>Selected elements will have their changes published as part of this workflow.</em></p>'.
                '<p><em>Save or refresh page to update list.</em></p>'
            )
            ->addExtraClass('wfa-right-elements-list');

        // readonly modifications
        if ($readonly) {
            $checklist->addExtraClass('wfa-readonly');
            $checklist->setDisabled(true);
            $checklist->setReadonly(true);
            $checklist->setValue($this->SelectedElements->getValue());
        }

        return $checklist;
    }

    /**
     * Get list of selected element IDs
     *
     * @return array
     */
    public function getSelectedElementsIDs()
    {
        $vals = $this->SelectedElements->getValue();
        return array_values($vals);
    }

    /**
     * Finds the last SelectElementsInstance in the given workflow
     *
     * @param WorkflowInstance $wfInstance
     * @return SelectElementsInstance|null
     */
    public static function findInWorkflow($wfInstance)
    {
        return $wfInstance->Actions()
            ->filter([ 'ClassName' => SelectElementsInstance::class ])
            ->sort('Created DESC')
            ->first();
    }

    /**
     * Creates html for a single checkbox option
     *
     * @param int $id
     * @param string $title
     * @param string $type
     * @return string
     */
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