<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Teacher editing of the topic tree (subject -> section -> topic ...).
 *
 * A topic that anything points at (questions, blueprint rules, LMS content,
 * student mastery, child topics) is never deleted, only deactivated, so
 * history and stage 5 statistics keep their references.
 */
class TopicTreeService
{
    /**
     * Nested tree of a subject with question counts (own + subtree).
     *
     * @return list<array<string, mixed>>
     */
    public function tree(Subject $subject): array
    {
        $topics = Topic::query()
            ->where('subject_id', $subject->id)
            ->orderBy('sort_order')
            ->orderBy('name_uz')
            ->get(['id', 'parent_id', 'code', 'name_uz', 'level', 'grade_from', 'grade_to', 'sort_order', 'is_active']);

        $counts = QuestionBank::query()
            ->where('subject_id', $subject->id)
            ->whereNotNull('topic_id')
            ->selectRaw('topic_id, review_status, count(*) as c')
            ->groupBy('topic_id', 'review_status')
            ->get()
            ->groupBy('topic_id');

        $byParent = $topics->groupBy(fn ($t) => $t->parent_id ?? '');

        $build = function (string $parentId) use (&$build, $byParent, $counts): array {
            return ($byParent[$parentId] ?? collect())->map(function (Topic $t) use ($build, $counts) {
                $children = $build($t->id);
                $own = $counts[$t->id] ?? collect();
                $total = (int) $own->sum('c') + array_sum(array_column($children, 'questions_total'));
                $approved = (int) $own->where('review_status', 'approved')->sum('c')
                    + array_sum(array_column($children, 'questions_approved'));

                return [
                    'id' => $t->id,
                    'parent_id' => $t->parent_id,
                    'code' => $t->code,
                    'name_uz' => $t->name_uz,
                    'level' => $t->level,
                    'grade_from' => $t->grade_from,
                    'grade_to' => $t->grade_to,
                    'is_active' => $t->is_active,
                    'questions_own' => (int) $own->sum('c'),
                    'questions_total' => $total,
                    'questions_approved' => $approved,
                    'children' => $children,
                ];
            })->values()->all();
        };

        return $build('');
    }

    /**
     * Flat, depth-first list for selects: [{id, name_uz, level, path, is_active, subject_id}].
     *
     * @return list<array<string, mixed>>
     */
    public function options(?Subject $subject = null): array
    {
        $topics = Topic::query()
            ->when($subject, fn ($q) => $q->where('subject_id', $subject->id))
            ->orderBy('sort_order')
            ->orderBy('name_uz')
            ->get(['id', 'subject_id', 'parent_id', 'code', 'name_uz', 'level', 'is_active']);

        $byParent = $topics->groupBy(fn ($t) => $t->parent_id ?? '');
        $out = [];
        $walk = function (string $parentId, string $path) use (&$walk, &$out, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $t) {
                $label = $path === '' ? $t->name_uz : $path.' › '.$t->name_uz;
                $out[] = [
                    'id' => $t->id,
                    'subject_id' => $t->subject_id,
                    'parent_id' => $t->parent_id,
                    'code' => $t->code,
                    'name_uz' => $t->name_uz,
                    'level' => $t->level,
                    'path' => $label,
                    'is_active' => $t->is_active,
                ];
                $walk($t->id, $label);
            }
        };
        $walk('', '');

        return $out;
    }

    /** @param array{name_uz: string, parent_id?: ?string, code?: ?string, grade_from?: ?int, grade_to?: ?int} $data */
    public function create(Subject $subject, array $data): Topic
    {
        $parent = ! empty($data['parent_id']) ? Topic::query()->findOrFail($data['parent_id']) : null;
        if ($parent && $parent->subject_id !== $subject->id) {
            throw new \InvalidArgumentException('A topic must belong to the subject of its parent.');
        }

        $code = trim((string) ($data['code'] ?? ''));
        $code = $code !== '' ? $code : $this->generateCode($subject, $parent, $data['name_uz']);

        $siblings = Topic::query()->where('subject_id', $subject->id)->where('parent_id', $parent?->id);

        return Topic::create([
            'subject_id' => $subject->id,
            'parent_id' => $parent?->id,
            'code' => $code,
            'name_uz' => trim($data['name_uz']),
            'grade_from' => $data['grade_from'] ?? $parent?->grade_from,
            'grade_to' => $data['grade_to'] ?? $parent?->grade_to,
            'sort_order' => ((int) $siblings->max('sort_order')) + 1,
            'is_active' => true,
        ]);
    }

    /** Rename / change grades / activate. Code stays: imports and AI drafts refer to it. */
    public function update(Topic $topic, array $data): Topic
    {
        $topic->fill(array_intersect_key($data, array_flip(['name_uz', 'grade_from', 'grade_to', 'is_active'])));
        $topic->save();

        // Re-activating a child under an inactive parent would hide it anyway: activate the ancestors.
        if ($topic->is_active && $topic->parent_id) {
            $parent = $topic->parent;
            while ($parent && ! $parent->is_active) {
                $parent->update(['is_active' => true]);
                $parent = $parent->parent;
            }
        }
        // Deactivating a section hides its whole subtree.
        if (array_key_exists('is_active', $data) && ! $topic->is_active) {
            Topic::query()->whereIn('id', $topic->descendantAndSelfIds())->update(['is_active' => false]);
        }

        return $topic;
    }

    /**
     * Move under another parent of the same subject (NULL = top level) at
     * the end of its new siblings. Levels of the whole subtree follow.
     */
    public function move(Topic $topic, ?string $parentId): Topic
    {
        return DB::transaction(function () use ($topic, $parentId) {
            if ($parentId === $topic->parent_id) {
                return $topic;
            }
            $topic->parent_id = $parentId;
            $topic->sort_order = ((int) Topic::query()
                ->where('subject_id', $topic->subject_id)
                ->where('parent_id', $parentId)
                ->max('sort_order')) + 1;
            $topic->save(); // model hook: same subject, no cycle, level = parent + 1

            $level = $topic->level;
            $frontier = [$topic->id];
            while ($frontier !== []) {
                $children = Topic::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
                if ($children === []) {
                    break;
                }
                $level++;
                Topic::query()->whereIn('id', $children)->update(['level' => $level]);
                $frontier = $children;
            }

            return $topic->refresh();
        });
    }

    /**
     * New order of siblings (drag-sort). All ids must share $parentId.
     *
     * @param  list<string>  $ids
     */
    public function reorder(Subject $subject, ?string $parentId, array $ids): void
    {
        $siblings = Topic::query()
            ->where('subject_id', $subject->id)
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->all();

        $ids = array_values(array_unique($ids));
        if (array_diff($ids, $siblings) !== []) {
            throw new \InvalidArgumentException('Only topics of one parent can be sorted together.');
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $id) {
                Topic::query()->whereKey($id)->update(['sort_order' => $i + 1]);
            }
        });
    }

    /** What still points at the topic (subtree included). Empty = safe to delete. */
    public function usage(Topic $topic): array
    {
        $ids = $topic->descendantAndSelfIds();
        $usage = [
            'children' => count($ids) - 1,
            'questions' => QuestionBank::withTrashed()->whereIn('topic_id', $ids)->count(),
            'blueprint_rules' => ExamBlueprintRule::query()->whereIn('topic_id', $ids)->count(),
            'masteries' => DB::table('exam_student_topic_mastery')->whereIn('topic_id', $ids)->count(),
        ];
        foreach (['courses', 'lessons', 'tests', 'lab_experiments'] as $table) {
            $usage[$table] = DB::table($table)->whereIn('exam_topic_id', $ids)->count();
        }

        return array_filter($usage);
    }

    /** @return 'deleted'|'deactivated' */
    public function removeOrDeactivate(Topic $topic): string
    {
        if ($this->usage($topic) !== []) {
            $this->update($topic, ['is_active' => false]);

            return 'deactivated';
        }
        $topic->delete();

        return 'deleted';
    }

    private function generateCode(Subject $subject, ?Topic $parent, string $name): string
    {
        $slug = Str::of(Str::ascii($name))->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(40, '')->toString();
        $base = ($parent?->code ?? $subject->code).'.'.($slug !== '' ? $slug : 'topic');
        $code = $base;
        $i = 2;
        while (Topic::withTrashed()->where('code', $code)->exists()) {
            $code = $base.'_'.$i++;
        }

        return $code;
    }

    /** Ids of the subtree, for "questions of this section" filters. */
    public function subtreeIds(string $topicId): array
    {
        $topic = Topic::query()->find($topicId);

        return $topic ? $topic->descendantAndSelfIds() : [];
    }
}
