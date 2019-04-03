<?php
namespace Symbiote\AdvancedWorkflow\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;

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
                $fields->insertBefore('TitleAndDisplayed', $msg);
            }
        }
    }

    public function canPublish($member = null)
    {
        if ($this->owner->hasMethod('getPage')) {
            if ($page = $this->owner->getPage()) {
                return $page->canPublish($member);
            }
        }
    }

    public function onBeforeWrite()
    {
        // need to mark our parent page as modified to ensure workflow requirements are
        // met
        $page = $this->owner->getPage();
        if ($page && $page->ID && $page->hasExtension(ElementalPageWorkflowExtension::class)) {
            $page->elementModified($this->owner);
            $page->write();
        }
    }
}
