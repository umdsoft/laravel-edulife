<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $tags = $query->withCount(['courses', 'posts']) // Assuming relationships exist
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    public function store(StoreTagRequest $request)
    {
        Tag::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
        ]);

        return back()->with('success', 'Teg yaratildi');
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
        ]);

        return back()->with('success', 'Teg yangilandi');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return back()->with('success', 'Teg o\'chirildi');
    }

    public function merge(Request $request)
    {
        $request->validate([
            'source_ids' => ['required', 'array'],
            'target_id' => ['required', 'exists:tags,id'],
        ]);

        $targetTag = Tag::findOrFail($request->target_id);
        $sourceTags = Tag::whereIn('id', $request->source_ids)->get();

        foreach ($sourceTags as $tag) {
            // Reassign relationships
            $tag->courses()->update(['tag_id' => $targetTag->id]); // Adjust based on actual pivot/relation
            // If many-to-many, it's more complex. Assuming simple relation or pivot update.
            // For now, let's assume standard pivot tables course_tag, post_tag
            
            // DB::table('course_tag')->where('tag_id', $tag->id)->update(['tag_id' => $targetTag->id]);
            // DB::table('post_tag')->where('tag_id', $tag->id)->update(['tag_id' => $targetTag->id]);
            
            $tag->delete();
        }

        return back()->with('success', 'Teglar birlashtirildi');
    }
}
