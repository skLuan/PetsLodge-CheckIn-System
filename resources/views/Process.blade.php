<x-app-layout>
    <x-slot name="scripts">
        @vite(['resources/js/cookies-and-form/form-processor.js', 'resources/js/tabbar.js'])
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
            <h2 id="thankYouTitle" class="text-2xl text-center font-bold mb-4 hidden">Thank You!</h2>

        </div>
        <div id="stepContainer" class="py-4 overflow-hidden relative min-h-[568px]" 
             data-session-checkin="{{ htmlspecialchars(json_encode(session('checkin_data', null)), ENT_QUOTES, 'UTF-8') }}"
             data-editing-mode="{{ session('editing_mode', false) ? 'true' : 'false' }}"
             data-editing-check-in-id="{{ session('editing_check_in_id', '') }}">
            <div id="step1" class="step w-full active z-10 bg-green-lightest">
                <h1 class="text-center">Your Information</h1>
                <p class="text-lg text-center">Please follow the instructions to complete your process</p>
                <x-forms.owner-info :user="$user ?? null" />
            </div>

            <div id="step2" class="step w-full">
                <h2 class="text-center font-bold">Pet Information</h2>
                <p class="text-lg">General Information of your best friend</p>
                <div id="fastCheckinPillsSection"
                     data-db-pets="{{ htmlspecialchars(json_encode(
                         ($user?->pets ?? collect())->map(fn($p) => [
                             'id'       => $p->id,
                             'petName'  => $p->name,
                             'petType'  => $p->kindOfPet?->name ?? 'other',
                             'petColor' => $p->color,
                             'petBreed' => $p->race,
                             'petAge'   => $p->birth_date,
                             'petWeight'=> $p->weight,
                             'petGender'=> $p->gender?->name ?? '',
                             'petSpayed'=> $p->castrated?->name ?? '',
                         ])->values()->all()
                     ), ENT_QUOTES, 'UTF-8') }}">
                    <p class="text-sm text-gray mb-1">Fast check-in for:</p>
                    <div id="fastCheckinPillsContainer" class="pills"></div>
                </div>
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
                    <p class="text-lg text-gray-700 mb-6">Please review your information and submit your check-in when
                        ready.
                    </p>
                    <div class="bg-white p-4 rounded-lg mb-6 border border-green border-opacity-40">
                        <h3 class="font-bold text-lg">Check-in Receipt</h3>
                        <x-check-in-summary :checkinData="session('checkin_data', [])" />
                    </div>

                    <div id="groomingPopup" class="grooming bg-white p-4 rounded-lg border border-gray-300 mb-6">
                        <h3 class="font-bold text-lg mb-4">Grooming Options</h3>
                        <div class="mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-gray-700">Want grooming before picking up?</h4>
                                <h3 class="mt-1 mb-3 text-sm">Take the service with 10% discount!</h3>
                            </div>
                            <div class="flex flex-col gap-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="groomingOptions[]" value="bath" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Bath</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="groomingOptions[]" value="nails" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Nails</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="groomingOptions[]" value="grooming" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Grooming</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="groomingOptions[]" value="no" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">No</span>
                                </label>
                            </div>
                        </div>

                        <!-- Conditional grooming appointment day -->
                        <div class="conditional-grooming-appointment-popup mb-4" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Preferred appointment day:
                            </label>
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center">
                                    <input type="radio" name="groomingAppointmentDay" value="Monday" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Monday</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="groomingAppointmentDay" value="Wednesday" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Wednesday</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="groomingAppointmentDay" value="Friday" class="mr-3 w-5 h-5">
                                    <span class="text-sm font-medium">Friday</span>
                                </label>
                            </div>
                        </div>

                        <!-- Conditional grooming notes textarea -->
                        <div class="conditional-grooming-notes-popup mb-4" style="display: none;">
                            <label for="groomingNotes" class="block text-sm font-medium text-gray-700 mb-2">
                                Grooming instructions & notes:
                            </label>
                            <textarea
                                class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                id="groomingNotes"
                                name="groomingNotes"
                                rows="3"
                                placeholder="Enter grooming instructions and any special notes"
                            ></textarea>
                        </div>

                        <div class="flex flex-col items-start justify-between mb-3">
                            <label for="groomingAcknowledged" class="flex items-center cursor-pointer">
                                <input type="checkbox" id="groomingAcknowledged"
                                    class="mr-3 h-4 w-4 focus:ring-green border-gray-300 rounded">
                                <span class="text-sm text-gray-700">I confirm my grooming preferences above</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Please confirm your grooming preferences before submitting.</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg border border-gray-300 mb-6">
                        <div class="flex flex-col items-center justify-between mb-3">
                            <label for="finalTermsAccepted" class="flex items-center cursor-pointer pl-0">
                                <input type="checkbox" id="finalTermsAccepted"
                                    class="mr-3 mb-0 h-4 w-4 focus:ring-green border-gray-300 rounded">
                                <span class="text-sm text-gray-700">I accept the <strong>Terms and
                                        Conditions</strong></span>
                            </label>
                            <button type="button" id="readTermsAgainBtn"
                                class="text-sm text-left text-blue-600 hover:text-blue-800 underline">
                                Read Terms Again
                            </button>
                        </div>
                        <p class="text-xs text-gray-500">By submitting this check-in, you agree to our terms and
                            conditions regarding pet care services.</p>
                    </div>
                    <div class="flex justify-center">
                        <button type="button" id="finalSubmit"
                            class="px-3 flex text-center justify-center flex-row items-center w-full shadow-md py-2 font-bold rounded-full bg-yellow-second text-gray transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            Submit Check-in
                            <iconify-icon class="ml-2 text-xl" icon="fluent:checkmark-20-filled"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Additional steps will be added here dynamically -->
        </div>
        <x-pop-ups.feeding-medication />
        <x-pop-ups.terms-conditions />
        <x-tabbar />
    </div>
</x-app-layout>
