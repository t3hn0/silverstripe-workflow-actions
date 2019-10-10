<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use Restruct\RightSidebar\RightSidebar;
use SilverStripe\Forms\RequiredFields;
use Symbiote\AdvancedWorkflow\Actions\RequireFieldsActionInstance;
use Symbiote\AdvancedWorkflow\Actions\SelectElementsInstance;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use SilverStripe\Forms\LiteralField;
use SilverStripe\i18n\i18n;

class RightsideWorkflow extends Extension
{
    private static $required_fields = [
        'Comment'
    ];

    public function updateWorkflowEditForm(Form $form)
    {
        $tab = $form->Fields()->findOrMakeTab('Root.WorkflowActions');
        if (!$tab) {
            return;
        }

        $changes = $form->Fields()->findOrMakeTab('Root.Changes');

        $sb = RightSidebar::create('WorkflowActions');
        foreach ($tab->Fields() as $f) {
            if (strpos($f->getName(), 'workflow-') !== false) {
                $changes->push($f);
            } else {
                $sb->push($f);
            }
        }

        $form->Fields()->removeByName('WorkflowActions');

        $form->Fields()->insertBefore($sb, 'Root');
        $form->Fields()->fieldByName('Root')->setTemplate('Restruct\RightSidebar\Forms\RightSidebarInner');

        // use any workflow settings for extra required fields
        $required = $this->owner->config()->required_fields;

        /** @var WorkflowService $service */
        $service = singleton(WorkflowService::class);
        /** @var DataObject|WorkflowApplicable $record */
        $record = $form->getRecord();
        $active = $service->getWorkflowFor($record);
        // see if the current step is the "require fields" step and highlight any
        // that are required
        $action = $active->CurrentAction();
        if ($action instanceof RequireFieldsActionInstance) {
            $needed = $action->getUnpopulatedFields();
            if (count($needed)) {
                $required = array_merge($required, $needed);
                $label = i18n::_t(
                    'RightsideWorkflow.REQUIRE_FIELDS',
                    "<span class='workflow-required'>Please ensure the following fields are populated: <strong>{fields}</strong></span>",
                        ['fields' => implode(', ', $needed)]
                );
                $sb->insertBefore(
                    'Comment',
                    LiteralField::create('RequireWfFields', $label)
                );
            }
        }

        // show readonly selected elements list where appropriate
        if (!($action instanceof SelectElementsInstance)) {
            if ($sei = SelectElementsInstance::findInWorkflow($active)) {
                $sb->push($sei->getSelectionList(true));
            }
        }

        foreach ($form->Fields()->dataFields() as $f) {
            if (in_array($f->getName(), $required)) {
                $f->setTitle($f->Title() . ' *');
            }
        }

        $validator = $form->getValidator();
        if (!$validator) {
            $validator = new RequiredFields();
        }
        if ($validator && $validator instanceof RequiredFields) {
            foreach ($required as $reqField) {
                $validator->addRequiredField($reqField);
            }
        }
        $form->setValidator($validator);
    }
}
