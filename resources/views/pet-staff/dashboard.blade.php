<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Pet Staff Dashboard</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Checked-In Pets Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4 text-blue-600">Checked-In Pets</h2>

                @if ($checkedInPets->isEmpty())
                    <p class="text-gray-500">No checked-in pets at the moment.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($checkedInPets as $checkIn)
                            <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">{{ $checkIn->pet->name }}</h3>
                                        <p class="text-sm text-gray-600">Owner: {{ $checkIn->user->name }}</p>
                                        <p class="text-sm text-gray-600">Type:
                                            {{ $checkIn->pet->kindOfPet->name ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">Contact: {{ $checkIn->user->phone }}</p>
                                    </div>
                                    <span
                                        class="px-3 py-1 bg-blue-200 text-blue-800 rounded-full text-xs font-semibold">
                                        CHECKED-IN
                                    </span>
                                </div>

                                <p class="text-xs text-gray-500 mb-4">
                                    Check-in: {{ $checkIn->check_in->format('M d, Y H:i') }}
                                </p>

                                <div class="flex gap-2">
                                    <form action="{{ route('pet-staff.dropped-in', $checkIn->id) }}" method="POST"
                                        class="flex-1">
                                        @csrf
                                        <button type="submit"
                                            class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                                            Mark as Dropped-In
                                        </button>
                                    </form>

                                    <form action="{{ route('pet-staff.cancel', $checkIn->id) }}" method="POST"
                                        class="flex-1">
                                        @csrf
                                        <button type="submit"
                                            class="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
                                            onclick="return confirm('Are you sure you want to cancel this check-in?')">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Dropped-In Pets Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4 text-orange-600">Dropped-In Pets</h2>

                @if ($droppedInPets->isEmpty())
                    <p class="text-gray-500">No dropped-in pets at the moment.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($droppedInPets as $checkIn)
                            <div class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">{{ $checkIn->pet->name }}</h3>
                                        <p class="text-sm text-gray-600">Owner: {{ $checkIn->user->name }}</p>
                                        <p class="text-sm text-gray-600">Type:
                                            {{ $checkIn->pet->kindOfPet->name ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">Contact: {{ $checkIn->user->phone }}</p>
                                    </div>
                                    <span
                                        class="px-3 py-1 bg-orange-200 text-orange-800 rounded-full text-xs font-semibold">
                                        DROPPED-IN
                                    </span>
                                </div>

                                <p class="text-xs text-gray-500 mb-4">
                                    Check-in: {{ $checkIn->check_in->format('M d, Y H:i') }}
                                </p>

                                <form action="{{ route('pet-staff.checkout', $checkIn->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                                        Checkout Pet
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
