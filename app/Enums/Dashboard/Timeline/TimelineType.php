<?php

namespace App\Enums\Dashboard\Timeline;

enum TimelineType: string
{
    case Task = 'task';
    case Visit = 'visit';
    case Inspection = 'inspection';
    case Deadline = 'deadline';
    case CorrectionRequest = 'correction-request';
    case CorrectionResponse = 'correction-response';
    case Hearing = 'hearing';
    case HearingSubmission = 'hearing-submission';
    case Complaint = 'complaint';
    case ComplaintAdditionalInformation = 'complaint-additional-information';
    case ComplaintDecision = 'complaint-decision';
}
