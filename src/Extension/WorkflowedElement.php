<?php

namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;

class WorkflowedElement extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $page = $this->owner->getPage();
        /** @var WorkflowService $service */
        $service = singleton(WorkflowService::class);
        /** @var DataObject|WorkflowApplicable $record */
        if ($page) {
            $active = $service->getWorkflowFor($page);
            if ($active) {
                $msg = LiteralField::create(
                    'wflMessage',
                    '<div class="message warning">This page is currently in workflow</div>'
                );
                $fields->insertBefore('Title', $msg);
            }
        }
    }

    /**
     * Whether the current user is an admin and is performing an
     * inline (graphql) publish on an individual element.
     *
     * @param Member $member The current user
     * @return boolean
     */
    public function canInlinePublish($member = null)
    {
        if (!Permission::checkMember($member, 'ADMIN')) {
            return false;
        }

        try {
            $req = Controller::curr()->getRequest();
            $data = json_decode($req->getBody());
            if ($data) {
                $pub_action = ($data->operationName == 'PublishBlock');
                $cur_block = ($data->variables->blockId == $this->owner->ID);
                return ($pub_action && $cur_block);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function canPublish($member = null)
    {
        /**
         * We don't use BaseElement::getPage() here as it can fail if the owner/element
         * was retrieved using Versioned::get_version().
         *
         * A simplification of why:
         * - Specific version of element loaded using query params.
         * - Query params stored on element as part of construction.
         * - When element loads a has-one, it uses DataObject::getComponent().
         * - getComponent uses stored query params (inc the element version).
         * - Thus getComponent tries to load related object with same version as $this.
         * - Wait, that's illegal!
         *
         * @see CopyToStage::resolve() to get started in understanding the steps below.
         */
        if ($this->owner->hasField('ParentID') && $this->owner->ParentID) {
            // if is admin doing graphql publish, allow it
            if ($this->canInlinePublish($member)) {
                return true;
            }
            $area = DataObject::get_by_id(ElementalArea::class, $this->owner->ParentID);
            $page = $area->getOwnerPage();
            return $page->canPublish($member);
        }
    }

    public function onAfterWrite()
    {
        // need to mark our parent page as modified to ensure workflow requirements are
        // met
        $changes = $this->owner->getChangedFields(true, DataObject::CHANGE_VALUE);
        unset($changes['Version']);
        $page = $this->owner->getPage();
        if (count($changes) && $page && $page->ID && $page->hasExtension(ElementalPageWorkflowExtension::class)) {
            $page->elementModified($this->owner);
            $page->write();
        }
    }
}
