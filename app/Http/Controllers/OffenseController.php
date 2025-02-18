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
        ->with('student', 'essay', 'teacher')
        ->get();

    $response = $essays->map(function ($offense) {
        return [
            'id' => $offense->essay->id,
            'student_name' => $offense->student->firstname . ' ' . $offense->student->lastname,
            'word_count' => $offense->word_count,
            'offense_count' => $offense->offense_count,
            'status' => $offense->completed ? 'Complete' : 'Incomplete',
            'teacher_name' => $offense->teacher->firstname . ' ' . $offense->teacher->lastname,
            'due_date' => $offense->due_date, // Assuming created_at as due date
            'reviewed' => $offense->essay->reviewed, // Assuming created_at as due date
        ];
    });

    return response()->json($response);
    }

    public function index1()
    {
        $teacherId = auth()->user()->id;

    $essays = Offense::where('teacher_id', $teacherId)
        ->with('student', 'essay', 'teacher')
        ->get();

    return response()->json($essays);
    }
    public function assignEssay(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'word_count' => 'required|integer'
        ]);

        $teacherId = JWTAuth::user()->id;
        
        $offense = new Offense();
        $offense->student_id = $request->student_id;
        $offense->teacher_id = $teacherId;
        $offense->word_count = $request->word_count;
        $offense->offense_count = Offense::where('student_id', $request->student_id)->count() + 1;
        $offense->completed = 0;
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
                'offense_count' => $essay->offense->offense_count,
                'status' => $essay->offense->completed ? 'Complete' : 'Incomplete',

            ];
        });
        return response()->json($response);
    }

    
}