<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->orderBy('name')->paginate(20);

        return view('admin.users.index', [
            'title' => __('users.page_title').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('users.page_title'), 'href' => route('users.index')],
            ],
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'title' => __('users.create_title').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('users.page_title'), 'href' => route('users.index')],
                ['title' => __('users.create_title'), 'href' => route('users.create')],
            ],
            'sites' => Site::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => 'en',
            'timezone' => 'UTC',
            'role' => $this->toUserRole($data['role']),
        ]);

        $this->syncAssignedSites($user, $data['site_ids'] ?? []);

        return redirect()->route('users.index')->with('success', __('users.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'title' => __('users.edit_title', ['name' => $user->name]).' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('users.page_title'), 'href' => route('users.index')],
                ['title' => $user->name, 'href' => route('users.edit', $user)],
            ],
            'user' => $user,
            'sites' => Site::query()->orderBy('name')->get(),
            'selectedSiteIds' => $user->assignedSites->pluck('id')->all(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $this->toUserRole($data['role']),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->syncAssignedSites($user, $data['site_ids'] ?? []);

        return redirect()->route('users.index')->with('success', __('users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->ownedSites()->exists()) {
            return redirect()->route('users.index')
                ->with('error', __('users.cannot_delete_user_with_sites'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', __('users.deleted'));
    }

    /**
     * @param  list<int|string>  $siteIds
     */
    private function syncAssignedSites(User $user, array $siteIds): void
    {
        if ($user->role === UserRole::Admin) {
            $user->assignedSites()->sync([]);

            return;
        }

        $user->assignedSites()->sync($siteIds);
    }

    private function toUserRole(UserRole|string $value): UserRole
    {
        return $value instanceof UserRole ? $value : UserRole::from($value);
    }
}
