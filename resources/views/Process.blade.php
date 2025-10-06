<x-app-layout>
    <x-slot name="scripts">
        @vite(['resources/js/cookies-and-form/form-processor.js'])
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Owner Info') }}
        </h2>
    </x-slot>

    <div class="container px-4 pb-8 max-w-screen-sm mx-auto">
        <div class="sticky top-0 z-20 bg-green-lightest py-3 border-b border-b-green">
            <x-progress.bar />
            <div id="petPillsContainer" class="pills"></div>
        </div>
        <div id="stepContainer" class="py-4 overflow-hidden relative min-h-[568px]">
            <div id="step1" class="step w-full active">
                <h1 class="text-center">Your Information</h1>
                <p class="text-lg text-center">Please follow the instructions to complete your process</p>
                <x-forms.owner-info :user="$user ?? null" />
            </div>

            <div id="step2" class="step w-full">
                <h2 class="text-center font-bold">Pet Information</h2>
                <p class="text-lg">General Information of your best friend</p>
                <x-forms.pet-info />
            </div>

            <div id="step3" class="step w-full relative">
                <h2 class="text-center font-bold">Feeding & medication</h2>
                <p class="text-lg">Please fill out your feeding instructions.</p>
                <x-forms.food-medication />
            </div>

            <div id="step4" class="step w-full">
                <x-forms.health-info />
            </div>

            <div id="step5" class="step w-full">
                <x-forms.inventory />
            </div>

            <div id="step6" class="step w-full">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-green-800 mb-4">Thank You!</h2>
                    <p class="text-lg text-gray-700 mb-6">Please review your information and submit your check-in when ready.</p>

                    <div class="bg-green-50 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold text-green-800 mb-2">Check-in Summary:</h3>
                        <div id="checkinSummary" class="text-left text-sm text-gray-600">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                    </div>

                    <button type="button" id="finalSubmit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition-colors duration-200">
                        Submit Check-in
                        <iconify-icon class="ml-2 text-xl" icon="fluent:checkmark-20-filled"></iconify-icon>
                    </button>
                </div>
            </div>
            <!-- Additional steps will be added here dynamically -->
        </div>
        <x-pop-ups.feeding-medication />
        <x-pop-ups.terms-conditions />
        <x-tabbar />
    </div>
</x-app-layout>
