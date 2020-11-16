<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\TextField;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;

class TargetMethodWorkflowAction extends WorkflowAction
{
    private static $db = [
        'TargetMethodName' => 'Varchar(64)',
    ];

    private static $table_name = 'TargetMethodWorkflowAction';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            new TextField('TargetMethodName', $this->fieldLabel('TargetMethodName')),
        ]);

        $this->extend('updateTargetMethodCMSFields', $fields);

        return $fields;
    }

    public function fieldLabels($relations = true)
    {
        return array_merge(parent::fieldLabels($relations), [
            'TargetMethodName' => _t('TargetMethodWorkflowAction.TARGETMETHODNAME', 'Target method name'),
        ]);
    }

    public function execute(WorkflowInstance $workflow)
    {
        $object = $workflow->getTarget();
        $method = $this->TargetMethodName;
        if ($method && ClassInfo::hasMethod($object, $method)) {
            return $object->$method($workflow) !== false;
        }
    }
}
