@extends('layouts.app')

@section('title', 'Categories & Subjects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-tags-fill text-primary me-2"></i> Categories & Subject Areas</h3>
        <p class="text-muted mb-0">Organize question banks and exams into structured domains.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-plus-circle me-1 text-primary"></i> Add New Category
            </div>
            <div class="card-body">
                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Computer Science & AI" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject Code</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. CS-AI (Leave blank to autogenerate)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief subject scope..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-save me-1"></i> Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Category List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-list-ul me-1"></i> Category Registry
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name & Description</th>
                                <th>Exams</th>
                                <th>Questions</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark font-monospace border">{{ $cat->code }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $cat->name }}</div>
                                        <small class="text-muted">{{ Str::limit($cat->description, 60) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $cat->exams_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $cat->questions_count }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No categories created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($categories->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
