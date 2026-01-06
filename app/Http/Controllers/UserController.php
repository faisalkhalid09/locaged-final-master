<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Support\Branding;
use App\Support\RoleHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // List all users
    public function index()
    {
        Gate::authorize('viewAny',User::class);

        $current = auth()->user();
        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($current);

        $users = User::with('roles')
            ->when(!empty($allowedRoleNames), function ($q) use ($allowedRoleNames) {
                $q->whereHas('roles', function ($qr) use ($allowedRoleNames) {
                    $qr->whereIn('name', $allowedRoleNames);
                });
            }, function ($q) {
                // If current user has no allowed roles (should not happen), hide all
                $q->whereRaw('1 = 0');
            })
            ->paginate(10);

        $roles = Role::select('name','id')
            ->when(auth()->check(), function ($q) {
                // For assignment, only allow roles strictly below current user's rank (except master)
                $allowed = RoleHierarchy::allowedAssignableRoleNamesFor(auth()->user());
                if (!empty($allowed)) {
                    $q->whereIn('name', $allowed);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->get();

        // Extra safety: remove same-rank role names for specific creators
        if ($current->hasRole('Department Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'department administrator');
        }
        if ($current->hasRole('Super Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'super administrator');
        }

        // Limit visible org structure based on creator role
        $departmentsQuery = Department::withoutGlobalScopes()->with('subDepartments.services');

        if ($current->hasRole('master') || $current->hasRole('Super Administrator')) {
            $departments = $departmentsQuery->get();
        } else {
            // Base department IDs on explicit department assignments first
            $deptIds = DB::table('department_user')
                ->where('user_id', $current->id)
                ->pluck('department_id');

            // Gather services and sub-departments explicitly assigned
            $serviceIds = DB::table('service_user')
                ->where('user_id', $current->id)
                ->pluck('service_id');

            $explicitSubDeptIds = DB::table('sub_department_user')
                ->where('user_id', $current->id)
                ->pluck('sub_department_id');

            // Also infer sub-departments from assigned services
            $serviceSubDeptIds = $serviceIds->isNotEmpty()
                ? DB::table('services')->whereIn('id', $serviceIds)->pluck('sub_department_id')
                : collect();

            $allSubDeptIds = $explicitSubDeptIds->merge($serviceSubDeptIds)->unique();

            // If no departments are directly assigned, infer them from sub-departments
            if ($deptIds->isEmpty() && $allSubDeptIds->isNotEmpty()) {
                $deptIds = DB::table('sub_departments')
                    ->whereIn('id', $allSubDeptIds)
                    ->pluck('department_id');
            }

            // Treat "Admin de departments" the same as the English alias "Division Chief"
            $isDivisionChief = $current->hasAnyRole(['Admin de departments', 'Division Chief']);
            $isServiceManager = $current->hasAnyRole(['Admin de cellule', 'service manager']);

            if ($isDivisionChief || $isServiceManager) {
                // Division Chief & Service Manager: only own departments and own sub-departments
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get()
                    ->map(function ($dept) use ($allSubDeptIds, $isServiceManager, $serviceIds) {
                        $subDepts = $dept->subDepartments
                            ->whereIn('id', $allSubDeptIds)
                            ->values();

                        // For service managers, also restrict services to those explicitly assigned
                        if ($isServiceManager && $serviceIds->isNotEmpty()) {
                            $subDepts->each(function ($subDept) use ($serviceIds) {
                                $subDept->setRelation(
                                    'services',
                                    $subDept->services->whereIn('id', $serviceIds)->values()
                                );
                            });
                        }

                        $dept->setRelation('subDepartments', $subDepts);
                        return $dept;
                    });
            } else {
                // Department Admin, Service User and others:
                // only own departments; keep all sub-departments/services under them
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get();
            }
        }

        return view('users.index',compact('users','roles','departments'));
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        return Excel::download(new UsersExport($request), 'users-' . now()->format('Ymd_His') . '.xlsx');
    }

    // Show a specific user
    public function show(User $user)
    {
        Gate::authorize('view',$user);

        $departments = Department::all();
        // For assignment in profile, use assignable roles (strictly lower rank except master)
        $roles = Role::whereIn('name', RoleHierarchy::allowedAssignableRoleNamesFor(auth()->user()))->get();

        // Extra safety: drop same-rank role from list when editing as dep/super admin
        $current = auth()->user();
        if ($current->hasRole('Department Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'department administrator');
        }
        if ($current->hasRole('Super Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'super administrator');
        }
        return view('users.profile',compact('user','departments','roles'));
    }

    /**
     * Show the authenticated user's own profile.
     * This route is accessible to all authenticated users.
     */
    public function showOwnProfile()
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403);
        }

        $departments = Department::all();
        // Minimal roles for self-view (not editable by regular users)
        $roles = collect();

        return view('users.profile', compact('user', 'departments', 'roles'));
    }

    /**
     * Update the authenticated user's own profile.
     * This route is accessible to all authenticated users for basic profile updates.
     */
    public function updateOwnProfile(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403);
        }

        $data = $request->validate([
            'full_name'  => 'string|max:255',
            'phone' => 'nullable|string|max:255',
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $data['image'] = $path;
        }
        unset($data['profile_image']);

        $user->update($data);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function audit()
    {
        Gate::authorize('viewAny',User::class);

        // Division Chief should not access audit page
        if (auth()->user()?->hasRole('Division Chief')) {
            abort(403);
        }

        $current = auth()->user();
        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($current);
        $isDeptAdmin = $current && (
            $current->hasRole('Department Administrator') ||
            $current->hasRole('Admin de pole')
        );
        
        $usersQuery = User::with('roles');
        
        // Department Administrator: only see users from their departments with lower roles
        if ($isDeptAdmin) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
            
            $usersQuery->when($deptIds->isNotEmpty(), function($q) use ($deptIds, $allowedRoleNames) {
                // User must be in one of the admin's departments
                $q->whereHas('departments', function($q2) use ($deptIds) {
                    $q2->whereIn('departments.id', $deptIds);
                })
                // AND user must have a role below the admin's rank
                ->whereHas('roles', function($q2) use ($allowedRoleNames) {
                    $q2->whereIn('name', $allowedRoleNames);
                });
            }, function($q) {
                // If no departments assigned, show nothing
                $q->whereRaw('1 = 0');
            });
        } else {
            // Other users: apply role hierarchy filtering
            $usersQuery->when(!empty($allowedRoleNames), function ($q) use ($allowedRoleNames) {
                $q->whereHas('roles', function ($qr) use ($allowedRoleNames) {
                    $qr->whereIn('name', $allowedRoleNames);
                });
            }, function ($q) {
                $q->whereRaw('1 = 0');
            });
        }
        
        $users = $usersQuery->paginate(10);

        return view('users.audit',compact('users'));
    }

    public function activity($id)
    {
        $user = User::findOrFail($id);
        Gate::authorize('view',$user);

        $auditLogs = $user->auditLogs()->with('document')->latest('occurred_at')->paginate(10); // Adjust per-page as needed

        return view('users.activity',compact('user','auditLogs'));
    }

    public function logs()
    {
        Gate::authorize('viewAny',User::class);

        // Division Chief should not access audit logs page
        if (auth()->user()?->hasRole('Division Chief')) {
            abort(403);
        }

        return view('users.logs');
    }


    // Store a new user
    public function store(Request $request)
    {
        Gate::authorize('create',User::class);

        // Check user limit before validation
        $maxUsers = Branding::getMaxUsers();
        if ($maxUsers > 0) {
            $currentUserCount = User::count();
            if ($currentUserCount >= $maxUsers) {
                throw ValidationException::withMessages([
                    'email' => ["Cannot create user. Maximum number of users ({$maxUsers}) has been reached."],
                ]);
            }
        }

        $selectedRoleId = $request->input('role');
        $selectedRoleName = null;
        if ($selectedRoleId) {
            $selectedRole = Role::find($selectedRoleId);
            $selectedRoleName = $selectedRole?->name;
        }

        $normalizedRoleName = $selectedRoleName ? strtolower($selectedRoleName) : '';

        // Default: org structure optional
        $departmentsRule = 'nullable|array';
        $subDepartmentsRule = 'nullable|array';
        $servicesRule = 'nullable|array';

        // Department Administrator, Division Chief, Service Manager, Service User must have at least one department
        if (in_array($normalizedRoleName, ['department administrator', 'division chief', 'service manager', 'service user'])) {
            $departmentsRule = 'required|array|min:1';
        }

        // Division Chief, Service Manager and Service User must have at least one sub-department
        if (in_array($normalizedRoleName, ['division chief', 'service manager', 'service user'])) {
            $subDepartmentsRule = 'required|array|min:1';
        }

        // Service Manager and Service User must have at least one service
        if (in_array($normalizedRoleName, ['service manager', 'service user'])) {
            $servicesRule = 'required|array|min:1';
        }

        // NEW: Check if admin wants to set password now
        $setPasswordNow = $request->boolean('set_password_now', false);

        $data = $request->validate([
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            // Password is now conditionally required
            'password'   => $setPasswordNow 
                ? ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
                : ['nullable'],
            'active'     => 'nullable|boolean',
            'role'       => 'nullable|exists:roles,id',
            'departments'   => $departmentsRule,
            'departments.*' => 'exists:departments,id',
            'sub_departments'   => $subDepartmentsRule,
            'sub_departments.*' => 'exists:sub_departments,id',
            'services'          => $servicesRule,
            'services.*'        => 'exists:services,id',
            'set_password_now'  => 'nullable|boolean',
        ], [
            'departments.required' => 'At least one structure must be selected.',
            'departments.min' => 'At least one structure must be selected.',
            'sub_departments.required' => 'At least one sub-department must be selected for this role.',
            'sub_departments.min' => 'At least one sub-department must be selected for this role.',
            'service_id.required' => 'A service must be selected for this role.',
        ]);

        // Determine primary sub-department and service (first in each list)
        $primarySubDeptId = isset($data['sub_departments'][0]) ? $data['sub_departments'][0] : null;
        $primaryServiceId = isset($data['services'][0]) ? $data['services'][0] : null;

        // Store plain text password before hashing (for email)
        $plainPassword = $data['password'] ?? null;

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'active' => $data['active'] ?? true,
            'username' => $data['email'],
            // If password provided, hash it; otherwise set random password that user can't use
            'password' => $plainPassword ? Hash::make($plainPassword) : Hash::make(bin2hex(random_bytes(32))),
            'locale' => 'fr', // Default locale is French
            'sub_department_id' => $primarySubDeptId,
            'service_id'        => $primaryServiceId,
        ]);

        if (isset($data['role'])) {
            $role = Role::findOrFail($data['role']);
            if (!RoleHierarchy::canAssignRole(auth()->user(), $role)) {
                throw ValidationException::withMessages([
                    'role' => ['You are not allowed to assign this role.'],
                ]);
            }
            $user->assignRole($role->name);
        }

        // Sync multiple departments
        $user->departments()->sync($data['departments'] ?? []);
        $user->subDepartments()->sync($data['sub_departments'] ?? ($primarySubDeptId ? [$primarySubDeptId] : []));
        $user->services()->sync($data['services'] ?? []);

        // NEW: Send appropriate email
        if ($setPasswordNow && $plainPassword) {
            // Admin set password: send credentials email
            \Mail::to($user->email)->send(new \App\Mail\UserCreatedWithPassword($user, $plainPassword));
        } else {
            // Admin didn't set password: send invitation email with setup link
            $setupUrl = \URL::temporarySignedRoute(
                'password.setup.show',
                now()->addHours(24),
                ['user' => $user->id]
            );
            \Mail::to($user->email)->send(new \App\Mail\UserInvitation($user, $setupUrl));
        }

        return redirect()->back()->with('success', 'User created successfully. An email has been sent to the user.');
    }

        // Update an existing user (Admins from Users management UI)
    public function update(Request $request, User $user)
    {
        Gate::authorize('update',$user);

        $rawRoleId = $request->input('role_id') ?: $request->input('role');
        $rawRoleName = null;
        if ($rawRoleId) {
            $rawRole = Role::find($rawRoleId);
            $rawRoleName = $rawRole?->name;
        }
        $normalizedRoleName = $rawRoleName ? strtolower($rawRoleName) : '';

        // Default: org structure optional on update as well
        $departmentsRule = 'nullable|array';
        $subDepartmentsRule = 'nullable|array';
        $servicesRule = 'nullable|array';

        if (in_array($normalizedRoleName, ['department administrator', 'division chief', 'service manager', 'service user'])) {
            $departmentsRule = 'required|array|min:1';
        }

        if (in_array($normalizedRoleName, ['division chief', 'service manager', 'service user'])) {
            $subDepartmentsRule = 'required|array|min:1';
        }

        // Service Manager and Service User must have at least one service
        if (in_array($normalizedRoleName, ['service manager', 'service user'])) {
            $servicesRule = 'required|array|min:1';
        }

        $data = $request->validate([
            'full_name'  => 'string|max:255',
            'email'      => ['email', Rule::unique('users')->ignore($user->id)],
            // phone no longer required
            'phone' => 'nullable|string|max:255',
            'active'     => 'nullable|boolean',
            'role_id'       => 'nullable|exists:roles,id',
            // Note: department_id removed - now using multi-department system via pivot table
'departments'   => $departmentsRule,
            'departments.*' => 'exists:departments,id',
            'sub_departments'   => $subDepartmentsRule,
            'sub_departments.*' => 'exists:sub_departments,id',
            'services'          => $servicesRule,
            'services.*'        => 'exists:services,id',
            'password'              => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Optional admin-led image update from Users management modal
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $data['image'] = $path;
        }

        // Remove departments, sub-departments and services from mass assignment
        $departments     = $data['departments'] ?? null;
        $subDepartments  = $data['sub_departments'] ?? null;
        $services        = $data['services'] ?? null;
        unset($data['departments'], $data['sub_departments'], $data['services']);

        $user->update($data);

        // Support role coming as role_id (existing) or role (modal select)
        $roleId = $data['role_id'] ?? $request->input('role');
        if ($roleId) {
            $role = Role::findOrFail($roleId);
            if (!RoleHierarchy::canAssignRole(auth()->user(), $role)) {
                throw ValidationException::withMessages([
                    'role' => ['You are not allowed to assign this role.'],
                ]);
            }
            $user->syncRoles($role->name);
        }

        // Sync multiple departments (may be optional depending on role)
        $user->departments()->sync($departments ?? []);

        // Sync sub-departments (multi-select via pivot)
        $user->subDepartments()->sync($subDepartments ?? []);

        // Sync services (multi-select)
        $user->services()->sync($services ?? []);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        Gate::authorize('updatePassword',$user);

        $data = $request->validate([
            'old_password'          => ['required', 'string'],
            'password'              => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Check old password
        if (!Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['The provided old password does not match our records.'],
            ]);
        }

        // Update new password
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function updateImage(Request $request, User $user)
    {
        $actor = auth()->user();
        if (! $actor) {
            abort(403);
        }

        // Allow user to update their own image, or users with update user permission
        if ($actor->id !== $user->id && ! $actor->can('update user')) {
            abort(403);
        }

        $data = $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $user->image = $path;
            $user->save();
        }

        return redirect()->back()->with('success', 'Profile image updated successfully.');
    }


    // Delete a user
    public function destroy(User $user)
    {
        Gate::authorize('delete',$user);


        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
