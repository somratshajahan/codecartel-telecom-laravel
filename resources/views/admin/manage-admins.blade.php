<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->page_title ?? 'Manage Admin Users' }} - {{ optional($settings)->company_name ?? 'Codecartel Telecom' }}</title>
    @if(optional($settings)->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset(optional($settings)->favicon_path) }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-base-200">
    <div class="drawer lg:drawer-open">
        <input id="my-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
                <div class="flex-none">
                    <label for="my-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </label>
                </div>
                <div class="flex-1"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold px-2 hover:text-primary transition-colors">{{ optional($settings)->company_name ?? 'Codecartel Telecom' }} - Manage Admin Users</a></div>
            </div>

            <div class="p-6">
                <div class="max-w-6xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Admin Users</h1>
                        @if($isFirstAdmin)
                            <button onclick="add_admin_modal.showModal()" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Admin User
                            </button>
                        @endif
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-error mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if(!$isFirstAdmin)
                        <div class="alert alert-info mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>You can only view admin users. Only the first admin can create new admin accounts.</span>
                        </div>
                    @endif

                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($admins as $admin)
                                            <tr>
                                                <td>{{ $admin->id }}</td>
                                                <td>
                                                    <div class="flex items-center gap-3">
                                                        <div class="avatar placeholder">
                                                            <div class="bg-primary text-primary-content rounded-full w-10">
                                                                @if($admin->profile_picture)
                                                                    <img src="{{ asset('storage/' . $admin->profile_picture) }}" alt="Profile">
                                                                @else
                                                                    <span>{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">{{ $admin->name }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $admin->email }}</td>
                                                <td>
                                                    @if($admin->is_first_admin)
                                                        <span class="badge badge-primary">First Admin</span>
                                                    @else
                                                        <span class="badge badge-secondary">Admin</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $admin->is_active ? 'badge-success' : 'badge-error' }}">
                                                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>{{ $admin->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No admin users found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="drawer-side z-40">
            <label for="my-drawer" class="drawer-overlay"></label>
            <aside id="sidebar" class="bg-base-100 w-64 min-h-screen border-r border-base-200">
                <div class="p-4 border-b border-base-200">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        @if(optional($settings)->company_logo_url)
                            <img src="{{ asset(optional($settings)->company_logo_url) }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg">
                        @else
                            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        <span class="text-lg font-bold">{{ optional($settings)->company_name ?? 'Codecartel' }}</span>
                    </a>
                </div>
                <ul class="menu p-4 gap-1">
                    <li><a href="{{ route('admin.dashboard') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>Dashboard</a></li>
                    <li><a href="{{ route('admin.backup') }}"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>Backup</a></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>Reseller</summary><ul><li><a href="{{ route('admin.resellers') }}">All Reseller</a></li></ul></details></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>Admin Account</summary><ul><li><a href="{{ route('admin.profile') }}">My Profile</a></li><li><a href="{{ route('admin.manage.admins') }}" class="active">Manage Admin Users</a></li><li><a href="{{ route('admin.change.credentials') }}">Change Password & PIN</a></li></ul></details></li>
                    <li><details><summary><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>Settings</summary><ul><li><a href="{{ route('admin.homepage.edit') }}">General Settings</a></li><li><a href="{{ route('admin.mail.config') }}">Mail Configuration</a></li><li><a href="{{ route('admin.sms.config') }}">Mobile OTP Configuration</a></li></ul></details></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 rounded-lg hover:bg-base-200 text-left">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </aside>
        </div>
    </div>

    <!-- Add Admin Modal -->
    @if($isFirstAdmin)
    <dialog id="add_admin_modal" class="modal">
        <div class="modal-box max-w-4xl">
            <h3 class="font-bold text-2xl mb-6 text-center">Create New Admin</h3>
            <form method="POST" action="{{ route('admin.store.admin') }}">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Side - Basic Info -->
                    <div>
                        <h4 class="font-semibold mb-3">Basic Information</h4>
                        <div class="space-y-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Name</span></label>
                                <input type="text" name="name" class="input input-bordered w-full" required />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Email</span></label>
                                <input type="email" name="email" class="input input-bordered w-full" required />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Password</span></label>
                                <input type="password" name="password" class="input input-bordered w-full" required />
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">PIN (4 digits)</span></label>
                                <input type="text" name="pin" maxlength="4" pattern="[0-9]{4}" class="input input-bordered w-full" placeholder="1234" required />
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Menu Permissions -->
                    <div>
                        <h4 class="font-semibold mb-3">Menu Permissions</h4>
                        <div class="form-control mb-3 bg-base-200 p-2 rounded">
                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" id="select_all" class="checkbox checkbox-primary checkbox-sm" onclick="toggleAll(this)" />
                                <span class="label-text font-semibold">Select All</span>
                            </label>
                        </div>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="dashboard" class="checkbox checkbox-primary checkbox-sm permission-checkbox" checked />
                                    <span class="label-text">Dashboard</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="backup" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Backup</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="recharge_history" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Recharge History</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="payment_history" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Payment History</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="manage_users" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Manage Users</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="manage_operators" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Manage Operators</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="manage_offers" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Manage Offers</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="payment_methods" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Payment Methods</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="support_tickets" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Support Tickets</span>
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" name="permissions[]" value="settings" class="checkbox checkbox-primary checkbox-sm permission-checkbox" />
                                    <span class="label-text">Settings</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 justify-center mt-6">
                    <button type="button" onclick="add_admin_modal.close()" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }
    </script>
    @endif
</body>
</html>
