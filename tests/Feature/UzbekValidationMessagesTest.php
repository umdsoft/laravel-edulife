<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-T-05: validation errors were English ("The title field is required.").
 */
class UzbekValidationMessagesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_the_app_speaks_uzbek_by_default(): void
    {
        $this->assertSame('uz', config('app.locale'));
        $this->assertSame("Olimpiadaga muvaffaqiyatli ro'yxatdan o'tdingiz", __('olympiad.registration.success'));
    }

    public function test_validation_errors_are_uzbek_with_field_names(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->post(route('teacher.courses.store'), [
            'title' => '',
            'description' => 'qisqa',
            'is_free' => false,
        ])->assertSessionHasErrors([
            'title' => "Nomi to'ldirilishi shart.",
            'description' => "Tavsif kamida 100 belgidan iborat bo'lishi kerak.",
            'price' => "Narx to'ldirilishi shart.",
        ]);

        $this->actingAs($teacher)->put(route('teacher.profile.update'), [
            'first_name' => 'A', 'last_name' => 'B', 'website' => 'not-a-url',
        ])->assertSessionHasErrors(['website' => "Vebsayt haqiqiy URL manzil bo'lishi kerak (https://…)."]);
    }

    /** QA2-S-07: the review form said "Comment to'ldirilishi shart." */
    public function test_every_form_request_field_has_an_uzbek_name(): void
    {
        $attributes = trans('validation.attributes', [], 'uz');
        $missing = [];

        foreach (\Illuminate\Support\Facades\File::allFiles(app_path('Http/Requests')) as $file) {
            preg_match_all("/^\s+'([a-z_]+(?:\.\*)?(?:\.[a-z_]+)?)' => \[/m", $file->getContents(), $m);
            foreach ($m[1] as $field) {
                if (! array_key_exists($field, $attributes)) {
                    $missing[] = $file->getFilename().': '.$field;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_review_comment_error_names_the_field_in_uzbek(): void
    {
        $validator = validator(['comment' => ''], ['comment' => 'required']);

        $this->assertSame("Sharh matni to'ldirilishi shart.", $validator->errors()->first('comment'));
    }
}
