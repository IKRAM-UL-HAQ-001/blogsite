@csrf

<div class="row g-3">
    <div class="col-lg-6">
        <label for="name" class="form-label">Name</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $source->name) }}" required autofocus>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label for="type" class="form-label">Type</label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="">Select type</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $source->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="url" class="form-label">URL</label>
        <input id="url" name="url" type="url" class="form-control @error('url') is-invalid @enderror" value="{{ old('url', $source->url) }}" required placeholder="https://example.com/feed.xml">
        @error('url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label for="is_active" class="form-label">Status</label>
        <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
            <option value="1" @selected((string) old('is_active', (int) $source->is_active) === '1')>Active</option>
            <option value="0" @selected((string) old('is_active', (int) $source->is_active) === '0')>Inactive</option>
        </select>
        @error('is_active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light">Cancel</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2-circle me-1"></i> {{ $buttonText }}
    </button>
</div>
