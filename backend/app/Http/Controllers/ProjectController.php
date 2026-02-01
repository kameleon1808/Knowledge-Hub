<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project', [
            'except' => ['index', 'create', 'store'],
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Project::query()
            ->visibleTo($user)
            ->with(['owner:id,name,email']);

        $projects = $query->orderBy('name')->paginate(15);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'can' => [
                'create' => $request->user()->can('create', Project::class),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = \Illuminate\Support\Facades\DB::transaction(function () use ($request): Project {
            $project = Project::create([
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description') ? $request->string('description')->toString() : null,
                'owner_user_id' => $request->user()->id,
            ]);
            $project->users()->attach($request->user()->id, ['role' => Project::ROLE_OWNER]);
            app(ActivityLogger::class)->log(ActivityLogger::ACTION_PROJECT_CREATED, $project, $request->user(), Project::class, $project->id);

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $activeTab = $request->query('tab', 'knowledge');
        $project->load(['owner:id,name,email']);
        $knowledgeItems = $activeTab === 'knowledge'
            ? $project->knowledgeItems()
                ->select(['id', 'project_id', 'type', 'title', 'status', 'error_message', 'created_at'])
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString()
                ->through(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'type' => $item->type,
                        'status' => $item->status,
                        'error_message' => $item->error_message,
                        'created_at' => $item->created_at?->toIso8601String(),
                    ];
                })
            : new LengthAwarePaginator([], 0, 15);
        $ragQueries = $activeTab === 'ask'
            ? $project->ragQueries()
                ->select(['id', 'project_id', 'user_id', 'question_text', 'answer_text', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'question_text' => $query->question_text,
                        'answer_text' => $query->answer_text,
                        'created_at' => $query->created_at?->toIso8601String(),
                    ];
                })
            : collect();
        $activityLogs = $activeTab === 'activity'
            ? $project->activityLogs()
                ->select(['id', 'project_id', 'actor_user_id', 'action', 'metadata', 'created_at'])
                ->with('actor:id,name')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'metadata' => $log->metadata,
                        'created_at' => $log->created_at?->toIso8601String(),
                        'actor' => $log->actor?->only(['id', 'name']),
                    ];
                })
            : collect();
        $members = $project->users()->get(['users.id', 'users.name', 'users.email'])->map(function ($u) use ($project) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $project->memberRole($u),
            ];
        });

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'owner' => $project->owner?->only(['id', 'name', 'email']),
            ],
            'knowledgeItems' => $knowledgeItems,
            'ragQueries' => $ragQueries,
            'activityLogs' => $activityLogs,
            'members' => $members,
            'activeTab' => $activeTab,
            'can' => [
                'update' => $request->user()->can('update', $project),
                'manageMembers' => $request->user()->can('manageMembers', $project),
                'addKnowledge' => $request->user()->can('addKnowledge', $project),
                'askRag' => $request->user()->can('askRag', $project),
                'export' => $request->user()->can('export', $project),
            ],
        ]);
    }

    public function edit(Request $request, Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update([
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description') ? $request->string('description')->toString() : null,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function searchMembers(Request $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $q = $request->string('q')->trim()->toString();
        if ($q === '') {
            return response()->json(['users' => []]);
        }

        $memberIds = $project->users()->pluck('users.id');
        $users = User::query()
            ->whereNotIn('id', $memberIds)
            ->where(function ($query) use ($q) {
                $query->where('email', 'ilike', '%' . $q . '%')
                    ->orWhere('name', 'ilike', '%' . $q . '%');
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        return response()->json(['users' => $users]);
    }

    public function addMember(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $userId = (int) $request->input('user_id');
        if ($project->users()->where('user_id', $userId)->exists()) {
            return redirect()->back()->with('error', 'User is already a member.');
        }

        $project->users()->attach($userId, ['role' => Project::ROLE_MEMBER]);

        return redirect()->back()->with('success', 'Member added.');
    }

    public function removeMember(Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        if ($project->isOwner($user)) {
            return redirect()->back()->with('error', 'Cannot remove the project owner.');
        }

        $project->users()->detach($user->id);

        return redirect()->back()->with('success', 'Member removed.');
    }
}
