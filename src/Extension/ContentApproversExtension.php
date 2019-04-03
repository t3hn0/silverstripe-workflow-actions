<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\Security\Group;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;


class ContentApproversExtension extends DataExtension
{
    private static $has_one	= array(
		'PublisherGroup'		=> Group::class,
		'ApproverGroup'		=> Group::class,
    );

    public function updateSettingsFields(FieldList $fields) {

		if (!Permission::check('APPLY_WORKFLOW')) {
			return;
		}

		$fields->addFieldToTab('Root.Workflow', new HeaderField('workflowGroupsHeader', 'Workflow groups options'), 'WorkflowLog');
		$groups = Group::get()->map()->toArray();

        $approverGroups = DropdownField::create('ApproverGroupID', 'Approver group', $groups)->setEmptyString('--select group--');
        $fields->addFieldToTab('Root.Workflow', $approverGroups, 'WorkflowLog');

        $approver = $this->getApprover();
        if ($approver) {
            $info = 'Inherited group: ' . $approver->Title;
            $approverGroups->setRightTitle($info);
        }

		$publisherGroups = DropdownField::create('PublisherGroupID', 'Publisher group', $groups)->setEmptyString('--select group--');
        $fields->addFieldToTab('Root.Workflow', $publisherGroups, 'WorkflowLog');

        $publisher = $this->getPublisher();
        if ($publisher) {
            $info = 'Inherited group: ' . $publisher->Title;
            $publisherGroups->setRightTitle($info);
        }
	}

	public function getApprover() {
		if ($this->owner->ApproverGroupID) {
			return $this->owner->ApproverGroup();
		}
		if ($this->owner->ParentID) {
            $p = $this->owner->Parent();
			return $p ? $p->getApprover() : null;
		}
	}

	public function getPublisher() {
		if ($this->owner->PublisherGroupID) {
			return $this->owner->PublisherGroup();
		}
		if ($this->owner->ParentID) {
            $p = $this->owner->Parent();
			return $p ? $p->getPublisher() : null;
		}
	}
}
