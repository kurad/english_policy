<?php

namespace App\Http\Controllers;

use App\Models\Essay;
use App\Models\Review;
use Illuminate\Http\Request;

class EssayReviewController extends Controller
{
    public function reviewEssay(Request $request, $essay_id)
{
    $request->validate([
        'comments' => 'required|string',
    ]);

    $essay = Essay::where('id', $essay_id)->first();

    if (!$essay) {
        return response()->json(['error' => 'Essay not found'], 404);
    }

    Review::create([
        'essay_id' => $essay_id,
        'teacher_id' => auth()->user()->id,
        'comments' => $request->comments,
    ]);

    return response()->json(['message' => 'Essay reviewed successfully']);
}
public function getEssaysWithReviews($essayId){

    $essay = Essay::with(['reviews.teacher'])->find($essayId);
    if(!$essay){
        return response()->json(['message'=>'Essay not found'],404);
    }
    $result = [
                'id' => $essay->id,
                'essay_content' => $essay->content,
                'reviews' => $essay->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'review_comments' => $review->comments,
                        'reviewer_name' => $review->teacher->firstname . ' ' . $review->teacher->lastname,
                ];
            }),
        ];

    return response()->json($result);
}

public function deleteReview($id)
{
    $review = Review::find($id);

    if (!$review) {
        return response()->json(['message' => 'Review not found'], 404);
    }
    $review->delete();

    return response()->json(['message' => 'Review deleted successfully']);
}
}