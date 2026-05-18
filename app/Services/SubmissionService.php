<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    /**
     * Save or update a draft submission
     * 
     * @param array $data Submission data
     * @param Submission|null $submission Existing submission (for edit mode)
     * @return Submission
     * @throws ValidationException
     */
    public function saveDraft(array $data, ?Submission $submission = null): Submission
    {
        return DB::transaction(function () use ($data, $submission) {
            $user = auth()->user();
            
            if (!$user) {
                throw new \RuntimeException('User must be authenticated to save drafts.');
            }

            // Validate required fields
            $this->validateDraftData($data);

            // Get template
            $template = Template::where('template_code', $data['template_code'])
                ->where('campus_code', $user->campus_code)
                ->first();
            
            if (!$template) {
                throw ValidationException::withMessages([
                    'template_code' => ['Template not found for your campus. Please select a valid template.']
                ]);
            }

            if ($template->status !== 'Published') {
                throw ValidationException::withMessages([
                    'template_code' => ['Template is not published and cannot be used for submissions.']
                ]);
            }

            // Extract quarter with fallback
            $quarter = $data['quarter'] 
                ?? (!empty($data['table_data']) && isset($data['table_data'][0]['quarter']) 
                    ? $data['table_data'][0]['quarter'] 
                    : null)
                ?? 'Unpublished';

            // Get campus name and code (aligned with Campus model for QA/approval flow)
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? 'Unknown';
            $campusCode = $user->campus_code;

            // Prepare submission data (include template_id and form_id so Form targets resolve correctly)
            $submissionData = [
                'template_id' => $template->id,
                'form_id' => $template->form_id,
                'template_code' => $template->template_code,
                'form_title' => $template->sg_code . ' - ' . $template->template_code,
                'sg_code' => $template->sg_code,
                'kra_title' => $template->kra_title,
                'kpi_title' => $template->kpi_title,
                'campus' => $campusName,
                'campus_code' => $campusCode,
                'quarter' => $quarter,
                'table_data' => $data['table_data'] ?? [],
                'status' => 'Unpublished',
                'is_draft' => true,
                'last_draft_at' => now(),
            ];

            if ($submission) {
                // Edit mode: Update existing submission
                if ((string)$submission->submitted_by !== (string)$user->id) {
                    throw new \RuntimeException('You can only edit your own submissions.');
                }

                // Only allow updating drafts or returned submissions
                if (!in_array($submission->status, ['Unpublished', 'Returned'])) {
                    throw ValidationException::withMessages([
                        'status' => ['Only draft or returned submissions can be updated.']
                    ]);
                }

                // If submission was returned, reset to draft status
                if ($submission->status === 'Returned') {
                    $submissionData['status'] = 'Unpublished';
                    $submissionData['is_draft'] = true;
                    // Reset draft version to 1 for returned submissions being reworked
                    $submissionData['draft_version'] = 1;
                } else {
                    // Increment draft version for existing drafts
                    $submissionData['draft_version'] = ($submission->draft_version ?? 0) + 1;
                }
                
                $submission->update($submissionData);
                
                return $submission->fresh();
            } else {
                // Create mode: Create or update draft
                $submission = Submission::updateOrCreate(
                    [
                        'submitted_by' => $user->id,
                        'template_code' => $template->template_code,
                        'status' => 'Unpublished',
                        'is_draft' => true,
                    ],
                    array_merge($submissionData, [
                        'draft_version' => 1,
                    ])
                );

                return $submission;
            }
        });
    }

    /**
     * Submit a draft or returned submission for review
     * Optionally update submission data before submitting
     * 
     * @param Submission $submission
     * @param array|null $data Optional data to update before submitting (template_code, table_data, quarter)
     * @return Submission
     */
    public function submitForReview(Submission $submission, ?array $data = null): Submission
    {
        return DB::transaction(function () use ($submission, $data) {
            $user = auth()->user();
            
            if ((string)$submission->submitted_by !== (string)$user->id) {
                throw new \RuntimeException('You can only submit your own submissions.');
            }

            // Allow both Draft and Returned submissions to be submitted for review
            if (!in_array($submission->status, ['Unpublished', 'Returned'])) {
                throw ValidationException::withMessages([
                    'status' => ['Only draft or returned submissions can be submitted for review.']
                ]);
            }

            // Store original status before any updates
            $originalStatus = $submission->status;
            $isReturned = $originalStatus === 'Returned';

            // If data is provided, update the submission first
            if ($data !== null) {
                // Validate the data
                $this->validateDraftData($data);
                
                // Get template (must allow user's campus)
                $candidates = Template::where('template_code', $data['template_code'])->get();
                $template = $user->campus_code
                    ? $candidates->first(fn ($t) => $t->allowsCampus($user->campus_code))
                    : $candidates->first(fn ($t) => $t->allowsAllCampuses());

                if (!$template) {
                    throw ValidationException::withMessages([
                        'template_code' => ['Template not found for your campus. Please select a valid template.']
                    ]);
                }

                if ($template->status !== 'Published') {
                    throw ValidationException::withMessages([
                        'template_code' => ['Template is not published and cannot be used for submissions.']
                    ]);
                }

                // Extract quarter with fallback
                $quarter = $data['quarter']
                    ?? (!empty($data['table_data']) && isset($data['table_data'][0]['quarter'])
                        ? $data['table_data'][0]['quarter']
                        : null)
                    ?? $submission->quarter
                    ?? 'Unpublished';

                // Get campus name and code (aligned with Campus model for QA/approval flow)
                $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? 'Unknown';
                $campusCode = $user->campus_code;

                // Prepare submission data (include template_id and form_id for Form targets)
                $submissionData = [
                    'template_id' => $template->id,
                    'form_id' => $template->form_id,
                    'template_code' => $template->template_code,
                    'form_title' => $template->sg_code . ' - ' . $template->template_code,
                    'sg_code' => $template->sg_code,
                    'kra_title' => $template->kra_title,
                    'kpi_title' => $template->kpi_title,
                    'campus' => $campusName,
                    'campus_code' => $campusCode,
                    'quarter' => $quarter,
                    'table_data' => $data['table_data'] ?? $submission->table_data ?? [],
                ];

                // If submission was returned, reset draft version
                if ($isReturned) {
                    $submissionData['draft_version'] = 1;
                }

                // Update submission data (but keep the original status for now)
                $submission->update($submissionData);
            }

            // Update submission to pending review status
            $submission->update([
                'status' => 'Pending Review',
                'is_draft' => false,
                'submitted_at' => now(),
                // Reset draft_version when resubmitting a returned submission (if not already set)
                'draft_version' => $isReturned ? 1 : ($submission->draft_version ?? 1),
            ]);

            return $submission->fresh();
        });
    }

    /**
     * Validate draft data
     * 
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDraftData(array $data): void
    {
        $rules = [
            'template_code' => 'required|exists:templates,template_code',
            'table_data' => 'nullable|array',
            'quarter' => 'nullable|string',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}

