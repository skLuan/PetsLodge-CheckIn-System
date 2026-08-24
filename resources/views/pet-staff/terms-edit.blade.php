<x-app-layout>
    <div class="container mx-auto px-4 py-8">

        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <h1 class="text-3xl font-bold">Edit Terms &amp; Conditions</h1>
            <a href="{{ route('pet-staff.dashboard') }}"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                &larr; Back to dashboard
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-lightest border border-green text-green rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Version banner: which version is live and who published it --}}
        <div class="mb-6 p-4 rounded border border-green-lightest bg-white-yellow">
            @if ($terms)
                <p class="font-semibold text-gray-800">
                    Version {{ $terms->version }} &middot; currently active
                </p>
                <p class="text-sm text-gray-600">
                    Last updated by {{ $terms->updatedBy->name ?? 'the system (initial import)' }}
                    on {{ $terms->updated_at->format('M d, Y H:i') }}
                </p>
            @else
                <p class="font-semibold text-red-600">
                    No active version yet — saving will publish version 1.
                </p>
            @endif
            <p class="text-sm text-gray-600 mt-2">
                Saving never overwrites the current text: it publishes a new version and keeps the
                old one on record, so agreements already signed stay traceable.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-300 text-red-700 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- The preview mirrors the textarea via Alpine. `content` is seeded from the
             textarea itself on init so the ~9KB of HTML isn't duplicated into an attribute. --}}
        <form method="POST" action="{{ route('pet-staff.terms.update') }}" x-data="{ content: '' }"
            x-init="content = $refs.editor.value">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $terms->title ?? 'Terms & Conditions') }}"
                    class="w-full rounded border-gray-300 shadow-sm focus:border-green focus:ring-green">
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Raw HTML editor --}}
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                        Content (HTML)
                    </label>
                    <textarea id="content" name="content" rows="26" x-ref="editor" x-model="content"
                        class="w-full font-mono text-xs rounded border-gray-300 shadow-sm focus:border-green focus:ring-green"
                        required>{{ old('content', $terms->content ?? '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Basic HTML is allowed (headings, paragraphs, lists, bold/italic, links, tables).
                        Scripts and other executable markup are removed on save.
                    </p>
                </div>

                {{-- Live preview of what the check-in popup will show --}}
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Preview</span>
                    <div class="border border-gray-300 rounded bg-white p-4 h-[34rem] overflow-y-auto"
                        x-html="content"></div>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit"
                    class="px-6 py-2 bg-green text-white rounded hover:bg-green-dark transition">
                    Publish new version
                </button>
                <a href="{{ route('pet-staff.dashboard') }}"
                    class="px-6 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
