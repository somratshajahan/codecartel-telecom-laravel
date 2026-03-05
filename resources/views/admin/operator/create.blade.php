@extends('admin') 
@section('content')
<div class="p-6">
    <div class="card bg-base-100 shadow-xl max-w-lg mx-auto">
        <div class="card-body">
            <h2 class="card-title">Add New Operator</h2>
            <form action="{{ route('admin.operator.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-control">
                    <label class="label"><span class="label-text">Operator Name</span></label>
                    <input type="text" name="name" class="input input-bordered" placeholder="e.g. Robi" required>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-full">Save Operator</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection