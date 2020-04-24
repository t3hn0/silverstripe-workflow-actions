<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Versioned\Versioned;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;

/**
 * Extends the set property action to allow chosing which
 * stage to set the property on
 *
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 */
class SetLivePropertyValueAction extends SetPropertyWorkflowAction
{
    private static $db = array(
        'OnStage' => "Varchar",
    );

    private static $table_name = 'SetLivePropertyValueAction';

    public function execute(WorkflowInstance $workflow)
    {
        if (!$target = $workflow->getTarget()) {
            return true;
        }

        if (parent::execute($workflow) && $this->OnStage == Versioned::LIVE) {
            $live = Versioned::get_by_stage($target->ClassName, Versioned::LIVE)->byID($target->ID);
            if ($live && $live->hasField($this->Property)) {
                $live->setField($this->Property, $this->Value);
                $live->writeToStage(Versioned::LIVE);
            }
        }

        return true;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $stages = [Versioned::DRAFT => 'Draft', Versioned::LIVE => 'Live'];

        $fields->addFieldsToTab('Root.Main', array(
            $stageDd = DropdownField::create('OnStage', 'Save to which stage?', $stages)
        ));

        $stageDd->setRightTitle("If writing to 'draft', item will need to be published later. If writing to live, just
            this value will be written on the live stage and no other pending changes");

        return $fields;
    }
}
