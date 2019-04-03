# Workflow Actions

[![Build Status](https://travis-ci.org/symbiote/silverstripe-workflow-actions.svg?branch=master)](https://travis-ci.org/symbiote/silverstripe-workflow-actions)
[![Latest Stable Version](https://poser.pugx.org/symbiote/silverstripe-workflow-actions/version.svg)](https://github.com/symbiote/silverstripe-workflow-actions/releases)
[![Latest Unstable Version](https://poser.pugx.org/symbiote/silverstripe-workflow-actions/v/unstable.svg)](https://packagist.org/packages/symbiote/silverstripe-workflow-actions)
[![Total Downloads](https://poser.pugx.org/symbiote/silverstripe-workflow-actions/downloads.svg)](https://packagist.org/packages/symbiote/silverstripe-workflow-actions)
[![License](https://poser.pugx.org/symbiote/silverstripe-workflow-actions/license.svg)](https://github.com/symbiote/silverstripe-workflow-actions/blob/master/LICENSE.md)

A set of workflow actions and extensions to extend the normal Advanced Workflow functionality

* Assign Content Approvers - allows you to specify an approver group and publisher group for particular
  content trees, that the workflow can use for assignment. Allows more flexible workflow definitions. 
* ElementalPageWorkflowExtension & WorkflowedElement - allows tracking of changes on elements on 
  parent pages to ensure workflow processes can be run at the parent page level. 
* RightsideWorkflow - Moves workflow interaction to a sidebar rather than being hidden on a tab

## Composer Install

```
composer require symbiote/silverstripe-workflow-actions:~1.0
```

## Requirements

* SilverStripe 4.1+

## Documentation

Add the following configuration, depending on needs

```
Page:
  extensions:
    - Symbiote\AdvancedWorkflow\Extension\ContentApproversExtension

# If using elemental, this helps track changes on elements in the containing
# parent page for review processes. 
Page:
  extensions:
    - Symbiote\AdvancedWorkflow\Extension\ElementalPageWorkflowExtension
BaseElement:
  extensions:
    - Symbiote\AdvancedWorkflow\Extension\WorkflowedElement
```

