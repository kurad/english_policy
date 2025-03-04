<?php

namespace App\Http\Controllers;

use App\Models\Essay;
use App\Models\Offense;
use App\Models\Review;
use Illuminate\Http\Request;

class EssayController extends Controller
{
    public function getStudentEssay(Request $request)
    {
        $studentId = $request->user()->id;

        $essays = Essay::whereHas('offense', function($query) use ($studentId){
            $query->where('student_id', $studentId);
        })->with(['offense.teacher'])->get();

        return response()->json($essays);
    }
    public function getEssay($essayId)
    {
        $essay = Essay::with('offense.teacher')
                ->findOrFail($essayId);
        return response()->json($essay);
    }
    public function getEssayForReview($id)
    {
        $essay = Essay::findOrFail($id);

        return response()->json($essay);

    }
    public function getReviewedEssay($id)
    {
        $essay = Essay::join('reviews','essays.id','=','reviews.essay_id')->where('id',$id);

        return response()->json($essay);

    }
    public function addComment(Request $request)
    {
        $validated = $request->validate([
            'comments' => 'required|string|max:65535', // Adjust max length as needed
            'essay_id' => 'required|exists:essays,id',
        ]);
        $comment = Review::create([
            'essay_id' => $validated['essay_id'],
            'teacher_id' => auth()->id(),
            'comments' => $validated['comments'],
        ]);

        // Update the reviewed status of the essay
        $essay = Essay::findOrFail($request->essay_id);
        $essay->reviewed = true;
        $essay->save();
        return response()->json($comment);
    }

    public function markAsReviewed($id)
    {
        $essay = Essay::findOrFail($id);
        $essay->reviewed = true;
        return response()->json(['message' => 'Essay marked as reviewed']);
    }
    public function saveDraft(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string'
        ]);
        $essay =Essay::findOrFail($id);
        $essay->content = $request->content;
        // $essay->save();
        $essay->update(['content' => $request->content]);

        return response()->json(['message' =>'Draft saved successfully']);
    }
    public function submitEssay(Request $request, $id)
    {
        $essay = Essay::findOrFail($id);
        $wordCount = str_word_count(strip_tags($request->content));
        if($wordCount < $essay->offense->word_count){
            return response()->json([
                'message' => "The essay must be at least {$essay->offense->word_count } words. It is now {$wordCount}"
            ], 422);
        }
        $essay->update([
            'content' =>$request->content,
            'status' =>'submitted',
        ]);
        $essay->offense->update(['completed' => 1]);

        return response()->json([
            'message' => 'Essay submitted successfully'
        ]);
    }
}
