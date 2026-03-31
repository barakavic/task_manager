<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                Rule::unique('tasks')->where(function ($query) use ($request) {
                    return $query->where('due_date', $request->input('due_date'));
                })
            ],
            'due_date' => 'required|date|after_or_equal:today',
            'priority' => ['required', Rule::in(['low', ' medium', 'high'])],
        ]);

        $task = Task::create([
            'title' => $validated['title'],
            'due_date' => $validated['due_date'],
            'priority' => $validated['priority'],
            'status' => 'pending',
        ]);

        return response()->json($task, 201);
    }

    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('due_date', 'asc')
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks exist.'], 200);
        }

        return response()->json($tasks);
    }

    public function updateStatus(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'done'])],
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $task->status;

        $validTransitions = [
            'pending' => 'in_progress',
            'in_progress' => 'done',
        ];

        if ($currentStatus === $newStatus) {
            return response()->json(['message' => 'Task is already in this status'], 400);
        }

        if (!isset($validTransitions[$currentStatus]) || $validTransitions[$currentStatus] !== $newStatus) {
            return response()->json(['message' => 'Invalid status transition. Status can only progress: pending -> in_progress -> done.'], 403);
        }

        $task->status = $newStatus;
        $task->save();

        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        if ($task->status !== 'done') {
            return response()->json(['message' => 'Forbidden. Only done tasks can be deleted.'], 403);
        }

        $task->delete();

        return response()->json(null, 204);
    }

    public function report(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = $request->input('date');

        $tasks = Task::whereDate('due_date', $date)
            ->select('priority', 'status', DB::raw('count(*) as total'))
            ->groupBy('priority', 'status')
            ->get();

        $summary = [
            'high' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'medium' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'low' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
        ];

        foreach ($tasks as $task) {
            if (isset($summary[$task->priority][$task->status])) {
                $summary[$task->priority][$task->status] = $task->total;
            }
        }

        return response()->json([
            'date' => $date,
            'summary' => $summary,
        ]);
    }
}
