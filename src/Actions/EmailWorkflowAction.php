<?php

namespace Symbiote\AdvancedWorkflow\Actions;

use Exception;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowAction;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\UserTemplates\UserTemplate;

class EmailWorkflowAction extends WorkflowAction
{
    private static $db = [
        'EmailTarget' => 'Enum("Manual,Field,Member,Group","Manual")',
        'EmailManual' => 'Varchar(64)',
        'EmailField' => 'Varchar(64)',
        'EmailFrom' => 'Varchar(64)',
        'EmailSubject' => 'Varchar(256)',
    ];

    private static $has_one = [
        'EmailTemplate' => UserTemplate::class,
        'EmailMember' => Member::class,
        'EmailGroup' => Group::class,
    ];

    private static $table_name = 'EmailWorkflowAction';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $newFields = FieldGroup::create('Email config');
        $newFields->push(LiteralField::create('CSS', '<style>.form__fieldgroup-item { min-width: 30% !important; }</style>'));

        // type
        $types = $this->dbObject('EmailTarget')->enumValues();
        $newFields->push(DropdownField::create('EmailTarget', $this->fieldLabel('EmailTarget'), $types));
        // from
        $newFields->push(TextField::create('EmailFrom', $this->fieldLabel('EmailFrom')));
        // subject
        $newFields->push(TextField::create('EmailSubject', $this->fieldLabel('EmailSubject')));
        // template
        $templates = UserTemplate::get()->filter(['Use' => 'Layout']);
        $newFields->push(DropdownField::create('EmailTemplateID', $this->fieldLabel('EmailTemplateID'), $templates)
            ->setEmptyString('-- default --'));
        // to
        $toTitle = _t('EmailWorkflowAction.EMAILTO', 'Send to');
        if ($this->EmailTarget === 'Manual') {
            $newFields->push(EmailField::create('EmailManual', $toTitle)->setDescription('Normal email address'));
        }
        else if ($this->EmailTarget === 'Field') {
            $newFields->push(TextField::create('EmailField', $toTitle)->setDescription('Name of target field'));
        }
        else if ($this->EmailTarget === 'Member') {
            $newFields->push(DropdownField::create('EmailMemberID', $toTitle, Member::get()->map()));
        }
        else if ($this->EmailTarget === 'Group') {
            $newFields->push(DropdownField::create('EmailGroupID', $toTitle, Group::get()->map()));
        }

        $fields->addFieldsToTab('Root.Main', $newFields);
        $this->extend('updateTargetMethodCMSFields', $fields);
        return $fields;
    }

    public function fieldLabels($relations = true)
    {
        return array_merge(parent::fieldLabels($relations), [
            'EmailTarget' => _t('EmailWorkflowAction.EMAILTARGET', 'Target type'),
            'EmailFrom' => _t('EmailWorkflowAction.EMAILFROM', 'Send from'),
            'EmailSubject' => _t('EmailWorkflowAction.EMAILSUBJECT', 'Email subject'),
            'EmailTemplateID' => _t('EmailWorkflowAction.EMAILTEMPLATE', 'Email template'),
        ]);
    }

    public function execute(WorkflowInstance $workflow)
    {
        $target = $workflow->getTarget();
        $from = $this->EmailFrom;
        $subject = $this->EmailSubject;
        $template = $this->EmailTemplate();

        if (!$target || !$from || !$subject || !$template) {
            return false;
        }

        $addys = [];
        if ($this->EmailTarget === 'Manual') {
            $addys[] = $this->EmailManual;
        }
        else if ($this->EmailTarget === 'Field') {
            $addys[] = $this->EmailField;
        }
        else if ($this->EmailTarget === 'Member') {
            if ($member = $this->EmailMember()) {
                if (filter_var($member->Email, FILTER_VALIDATE_EMAIL)) {
                    $addys[] = $member->Email;
                }
            }
        }
        else if ($this->EmailTarget === 'Group') {
            if ($group = $this->EmailGroup()) {
                foreach ($group->Members() as $member) {
                    if (filter_var($member->Email, FILTER_VALIDATE_EMAIL)) {
                        $addys[] = $member->Email;
                    }
                }
            }
        }

        try {
            $body = $target->customise(['FromEmail' => $from])->renderWith($template->getTemplateFile());
            foreach ($addys as $to) {
                $email = Email::create();
                $email->setTo($to);
                $email->setFrom($from);
                $email->setSubject($subject);
                $email->setBody($body->getValue());
                $email->setPlainTemplate($body->Plain());
                $email->send();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}
