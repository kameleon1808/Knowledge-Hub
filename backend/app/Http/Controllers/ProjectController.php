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

        $project->load(['owner:id,name,email']);
        $project->load(['knowledgeItems' => fn ($q) => $q->orderByDesc('created_at')]);
        $ragQueries = $project->ragQueries()->with('user:id,name')->orderByDesc('created_at')->limit(20)->get();
        $activityLogs = $project->activityLogs()->with('actor:id,name')->orderByDesc('created_at')->limit(50)->get();
        $members = $project->users()->get(['users.id', 'users.name', 'users.email'])->map(function ($u) use ($project) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $project->memberRole($u),
            ];
        });

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'knowledgeItems' => $project->knowledgeItems,
            'ragQueries' => $ragQueries,
            'activityLogs' => $activityLogs,
            'members' => $members,
            'activeTab' => $request->query('tab', 'knowledge'),
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
