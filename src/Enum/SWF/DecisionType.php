<?php namespace Phlow\Enum\SWF;

class DecisionType
{
    const SCHEDULE_ACTIVITY_TASK = 'ScheduleActivityTask';
    const REQUEST_CANCEL_ACTIVITY_TASK = 'RequestCancelActivityTask';
    const COMPLETE_WORKFLOW_EXECUTION = 'CompleteWorkflowExecution';
    const FAIL_WORKFLOW_EXECUTION = 'FailWorkflowExecution';
    const CANCEL_WORKFLOW_EXECUTION = 'CancelWorkflowExecution';
    const CONTINUE_AS_NEW_WORKFLOW_EXECUTION = 'ContinueAsNewWorkflowExecution';
    const RECORD_MARKER = 'RecordMarker';
    const START_TIMER = 'StartTimer';
    const CANCEL_TIMER = 'CancelTimer';
    const SIGNAL_EXTERNAL_WORKFLOW_EXECUTION = 'SignalExternalWorkflowExecution';
    const REQUEST_CANCEL_EXTERNAL_WORKFLOW_EXECUTION = 'RequestCancelExternalWorkflowExecution';
    const START_CHILD_WORKFLOW_EXECUTION = 'StartChildWorkflowExecution';
    const SCHEDULE_LAMBDA_FUNCTION = 'ScheduleLambdaFunction';
}
