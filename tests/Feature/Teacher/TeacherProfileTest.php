<?php

namespace Tests\Feature\Teacher;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherProfileTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_creating_a_teacher_creates_the_teacher_profile_row(): void
    {
        $teacher = $this->teacher();

        $this->assertSame(1, TeacherProfile::where('user_id', $teacher->id)->count());
    }

    public function test_promoting_a_user_to_teacher_creates_the_teacher_profile_row(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->assertNull($user->teacherProfile);

        $user->update(['role' => 'teacher']);
        $user->update(['first_name' => 'Yangi']); // no duplicate on later saves

        $this->assertSame(1, TeacherProfile::where('user_id', $user->id)->count());
    }

    public function test_profile_pages_work_for_a_teacher_without_a_profile_row(): void
    {
        $teacher = $this->teacher();
        TeacherProfile::where('user_id', $teacher->id)->delete();

        $this->actingAs($teacher)->get(route('teacher.profile.index'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.profile.edit'))->assertOk();
        $this->assertSame(1, TeacherProfile::where('user_id', $teacher->id)->count());
    }

    public function test_profile_update_saves_every_form_field(): void
    {
        $teacher = $this->teacher();
        TeacherProfile::where('user_id', $teacher->id)->delete();

        $this->actingAs($teacher)->put(route('teacher.profile.update'), [
            'first_name' => 'Aziz',
            'last_name' => 'Karimov',
            'email' => 'aziz@example.com',
            'headline' => 'Matematika ustozi',
            'bio' => 'Olimpiada tayyorlovi',
            'website' => 'https://aziz.uz',
            'experience_years' => 7,
            'expertise' => ['Algebra', 'Geometriya', ''],
            'social_links' => ['telegram' => 'https://t.me/aziz', 'instagram' => ''],
        ])->assertRedirect(route('teacher.profile.index'))->assertSessionHasNoErrors();

        $teacher->refresh();
        $this->assertSame('Aziz', $teacher->first_name);
        $this->assertSame('Olimpiada tayyorlovi', $teacher->bio);
        $this->assertSame('https://aziz.uz', $teacher->website);
        $this->assertSame(['telegram' => 'https://t.me/aziz'], $teacher->social_links);

        $profile = $teacher->teacherProfile;
        $this->assertSame('Matematika ustozi', $profile->headline);
        $this->assertSame('Olimpiada tayyorlovi', $profile->bio);
        $this->assertSame(['Algebra', 'Geometriya'], $profile->expertise);
        $this->assertSame(7, $profile->experience_years);
        $this->assertSame('https://t.me/aziz', $profile->telegram_url);
    }

    public function test_avatar_upload_is_stored_on_the_avatar_column(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();

        $this->actingAs($teacher)->post(route('teacher.profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ])->assertSessionHasNoErrors();

        $path = $teacher->fresh()->avatar;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($teacher)->delete(route('teacher.profile.delete-avatar'));
        $this->assertNull($teacher->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }
}
