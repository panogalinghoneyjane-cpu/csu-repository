<form action="{{ route('search') }}" method="GET" class="flex gap-2">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search by title, author, keyword, or subject..."
           class="flex-1 border rounded-lg px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-csu-600">
    <button class="bg-csu-700 text-white px-5 rounded-lg hover:bg-csu-800">Search</button>
</form>
