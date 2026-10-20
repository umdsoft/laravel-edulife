<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\Media\InvalidQuestionImage;
use App\Services\Media\QuestionImageStore;
use App\Support\RichText\RichTextMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Rasm" button of MarkdownEditor (upload, paste, drag-drop): teacher / admin
 * only (route middleware), rate limited. Returns the URL and the Markdown to
 * insert; the image is not linked to anything yet — an upload that no text
 * references is removed by media:prune-question-images after 7 days.
 */
class QuestionImageController extends Controller
{
    public function store(Request $request, QuestionImageStore $store): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,webp,gif'],
            'alt' => ['nullable', 'string', 'max:120'],
        ], [
            'image.required' => 'Rasm faylini tanlang.',
            'image.max' => 'Rasm hajmi 5 MB dan oshmasin.',
            'image.mimes' => 'Faqat PNG, JPG, WebP yoki GIF rasm yuklash mumkin (SVG qabul qilinmaydi).',
            'image.file' => 'Rasm faylini tanlang.',
        ]);

        try {
            $saved = $store->storeUpload($request->file('image'));
        } catch (InvalidQuestionImage $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['image' => [$e->getMessage()]]], 422);
        }

        return response()->json([
            'url' => $saved['url'],
            'width' => $saved['width'],
            'height' => $saved['height'],
            'markdown' => RichTextMedia::imageMarkdown($saved['url'], (string) $request->input('alt', 'chizma')),
        ], 201);
    }
}
