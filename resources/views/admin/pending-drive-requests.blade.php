@extends('admin')

@section('content')
<div class="p-6">
    <h1 class="text-3xl font-bold mb-6">Pending Drive Requests</h1>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Operator</th>
                            <th>Package</th>
                            <th>Mobile</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->user->name }}</td>
                                <td><span class="badge badge-primary">{{ $request->operator }}</span></td>
                                <td>{{ $request->package->name ?? 'N/A' }}</td>
                                <td>{{ $request->mobile }}</td>
                                <td>৳{{ number_format($request->amount, 2) }}</td>
                                <td><span class="badge badge-warning">{{ ucfirst($request->status) }}</span></td>
                                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <form method="POST" action="/admin/drive-requests/{{ $request->id }}/approve" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="/admin/drive-requests/{{ $request->id }}/reject" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-error btn-sm">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No pending requests</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
