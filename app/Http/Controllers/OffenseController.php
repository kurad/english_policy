<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Essay;
use App\Models\Offense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class OffenseController extends Controller
{
    public function index()
    {
        $teacherId = auth()->user()->id;

$essays = Offense::where('teacher_id', $teacherId)
    ->with(['student', 'essay', 'teacher'])
    ->join('essays', 'offenses.id', '=', 'essays.offense_id') // Join with essays to order by status
    ->where('essays.reviewed', 0)
    ->orderBy('essays.status', 'desc') // Order by essay status (0 = Not Completed, 1 = Completed)
    ->select('offenses.*') // Select only offenses columns to avoid conflicts
    ->get();

$response = $essays->map(function ($offense) {
    return [
        'id' => $offense->essay->id ?? null, // Handle possible null essay
        'student_name' => optional($offense->student)->firstname . ' ' . optional($offense->student)->lastname,
        'word_count' => $offense->word_count,
        'essay_topic' => $offense->essay_topic,
        'offense_count' => $offense->offense_count,
        'status' => optional($offense->essay)->status ? 'Completed' : 'Not Yet Completed',
        'teacher_name' => optional($offense->teacher)->firstname . ' ' . optional($offense->teacher)->lastname,
        'due_date' => $offense->due_date,
        'reviewed' => optional($offense->essay)->reviewed,
    ];
});


    return response()->json($response);
    }
    public function getReviewedEssays()
{
    $teacherId = auth()->user()->id;

    $essays = Offense::where('teacher_id', $teacherId)
        ->with(['student', 'essay', 'teacher'])
        ->join('essays', 'offenses.id', '=', 'essays.offense_id')
        ->where('essays.status', 1) // Ensure the essay is completed
        ->where('essays.reviewed', 1) // Ensure it has been reviewed
        ->orderBy('essays.updated_at', 'desc') // Order by last update time
        ->select('offenses.*')
        ->get();

    $response = $essays->map(function ($offense) {
        return [
            'id' => $offense->essay->id ?? null,
            'student_name' => optional($offense->student)->firstname . ' ' . optional($offense->student)->lastname,
            'word_count' => $offense->word_count,
            'essay_topic' => $offense->essay_topic,
            'offense_count' => $offense->offense_count,
            'status' => 'Reviewed',
            'teacher_name' => optional($offense->teacher)->firstname . ' ' . optional($offense->teacher)->lastname,
            'due_date' => $offense->due_date,
            'reviewed' => 1,
        ];
    });

    return response()->json($response);
}

    public function assignEssay(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'word_count' => 'required|integer',
            'essay_topic' => 'required|string|max:255',
        ]);

        $teacherId = JWTAuth::user()->id;
        
        $offense = new Offense();
        $offense->student_id = $request->student_id;
        $offense->teacher_id = $teacherId;
        $offense->word_count = $request->word_count;
        $offense->essay_topic = $request->essay_topic;
        $offense->offense_count = Offense::where('student_id', $request->student_id)->count() + 1;
        $offense->due_date = Carbon::now()->addDays(7);
        $offense->save();

        $essay = new Essay();
        $essay->offense_id = $offense->id;
        $essay->content ='';
        $essay->save();

        
        return response()->json(['message' => 'Essay assigned successfully', 'offense' =>$offense], 200);
    }
    public function getAssignedEssay(){
        $user = auth()->user();
        if($user->role != 'teacher'){
           $essay = Offense::with('student', 'teacher')
            ->where('teacher_id', $user->id)
            ->get();
        }else{
            $essay = Offense::with('student','teacher')
                ->where('student_id', $user->id)
                ->get();
        }
        $response = $essay->map(function($essay){
            return [
                'id' => $essay->id,
                'student_name' => $essay->student->firstname . ' ' . $essay->student->lastname,
                'teacher_name' => $essay->teacher->firstname . ' ' . $essay->teacher->lastname,
                'word_count' => $essay->offense->word_count,
                'topic' => $essay->offense->essay_topic,
                'offense_count' => $essay->offense->offense_count,
                'status' => $essay->offense->status ? 'Complete' : 'Incomplete',

            ];
        });
        return response()->json($response);
    }
    public function showAssignmentToUpdate($id)
    {
        $offense = Offense::findOrFail($id);
        return response()->json($offense);
    }
    public function updateAssignment(Request $request, $id){
        $request->validate([
            'word_count' => 'required|integer|min:100',
            'essay_topic' => 'required|string|max:255',
            'due_date' => 'required|date',
        ]);
        $essay = Offense::findOrFail($id);
        $essay->update([
            'word_count'=>$request->word_count,
            'essay_topic' =>$request->essay_topic,
            'due_date' => $request->due_date,
        ]);
        return response([
            'message' =>'Assignment updated successfully',
            'essay' =>$essay,
        ]);
    }
    public function deleteAssignedEssay($id)
{
    $teacherId = JWTAuth::user()->id;
    $offense = Offense::findOrFail($id);

    if (!$offense) {
        return response()->json(['message' => 'Offense not found'], 404);
    }
    if ($offense->teacher_id !== $teacherId) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $studentId = $offense->student_id;
    Essay::where('offense_id', $offense->id)->delete();

    $offense->delete();
    $newOffenseCount = Offense::where('student_id', $studentId)->count();
    $offenses = Offense::where('student_id', $studentId)->orderBy('created_at')->get();
    foreach ($offenses as $index => $off) {
        $off->offense_count = $index + 1;
        $off->save();
    }

    return response()->json([
        'message' => 'Assigned essay deleted successfully',
        'new_offense_count' => $newOffenseCount
    ], 200);
}
    
}
