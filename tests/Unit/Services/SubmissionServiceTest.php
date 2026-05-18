<?php

namespace Tests\Unit\Services;

use App\Models\Campus;
use App\Models\Submission;
use App\Models\Template;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubmissionService $service;
    protected User $user;
    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SubmissionService();

        Campus::query()->firstOrCreate(
            ['code' => 'TEST'],
            [
                'name' => 'Test Campus',
                'location' => '',
                'description' => '',
                'is_active' => true,
            ]
        );

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'creator_editor',
            'campus_code' => 'TEST',
        ]);
        
        // Create a test template
        $this->template = Template::factory()->create([
            'campus_code' => 'TEST',
            'status' => 'Published',
            'template_code' => 'TEST-T1',
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_save_a_new_draft()
    {
        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [
                ['quarter' => '1st Q', 'value' => '100']
            ],
            'quarter' => '1st Q',
        ];

        $submission = $this->service->saveDraft($data);

        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertTrue($submission->is_draft);
        $this->assertEquals(1, $submission->draft_version);
        $this->assertEquals('Unpublished', $submission->status);
        $this->assertNotNull($submission->last_draft_at);
        $this->assertEquals($this->user->id, $submission->submitted_by);
    }

    /** @test */
    public function it_can_update_an_existing_draft()
    {
        // Create an existing draft
        $existingDraft = Submission::factory()->create([
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
            'status' => 'Unpublished',
            'is_draft' => true,
            'draft_version' => 1,
        ]);

        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [
                ['quarter' => '1st Q', 'value' => '200']
            ],
            'quarter' => '1st Q',
        ];

        $submission = $this->service->saveDraft($data, $existingDraft);

        $this->assertEquals($existingDraft->id, $submission->id);
        $this->assertEquals(2, $submission->draft_version);
        $this->assertTrue($submission->is_draft);
        $this->assertNotNull($submission->last_draft_at);
    }

    /** @test */
    public function it_increments_draft_version_on_update()
    {
        $existingDraft = Submission::factory()->create([
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
            'status' => 'Unpublished',
            'is_draft' => true,
            'draft_version' => 3,
        ]);

        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [],
        ];

        $submission = $this->service->saveDraft($data, $existingDraft);

        $this->assertEquals(4, $submission->draft_version);
    }

    /** @test */
    public function it_throws_exception_when_updating_other_users_submission()
    {
        $otherUser = User::factory()->create();
        $otherUserDraft = Submission::factory()->create([
            'submitted_by' => $otherUser->id,
            'template_code' => $this->template->template_code,
            'status' => 'Unpublished',
        ]);

        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You can only edit your own submissions.');

        $this->service->saveDraft($data, $otherUserDraft);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_draft_submission()
    {
        $approvedSubmission = Submission::factory()->create([
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
            'status' => 'Approved',
            'is_draft' => false,
        ]);

        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [],
        ];

        $this->expectException(ValidationException::class);

        $this->service->saveDraft($data, $approvedSubmission);
    }

    /** @test */
    public function it_allows_updating_returned_submissions()
    {
        $returnedSubmission = Submission::factory()->create([
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
            'status' => 'Returned',
            'is_draft' => false,
        ]);

        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [],
        ];

        $submission = $this->service->saveDraft($data, $returnedSubmission);

        $this->assertEquals($returnedSubmission->id, $submission->id);
        $this->assertTrue($submission->is_draft);
        $this->assertEquals('Unpublished', $submission->status);
    }

    /** @test */
    public function it_throws_exception_for_unpublished_template()
    {
        $unpublishedTemplate = Template::factory()->create([
            'campus_code' => 'TEST',
            'status' => 'Unpublished',
            'template_code' => 'TEST-T2',
        ]);

        $data = [
            'template_code' => $unpublishedTemplate->template_code,
            'table_data' => [],
        ];

        $this->expectException(ValidationException::class);

        $this->service->saveDraft($data);
    }

    /** @test */
    public function it_can_submit_draft_for_review()
    {
        $draft = Submission::factory()->create([
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
            'status' => 'Unpublished',
            'is_draft' => true,
        ]);

        $submission = $this->service->submitForReview($draft);

        $this->assertEquals('Pending Review', $submission->status);
        $this->assertFalse($submission->is_draft);
        $this->assertNotNull($submission->submitted_at);
    }

    /** @test */
    public function save_draft_persists_submission_to_database()
    {
        $data = [
            'template_code' => $this->template->template_code,
            'table_data' => [],
        ];

        $submission = $this->service->saveDraft($data);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'submitted_by' => $this->user->id,
            'template_code' => $this->template->template_code,
        ]);
    }
}

