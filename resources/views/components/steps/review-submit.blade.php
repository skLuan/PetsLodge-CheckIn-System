@props(['checkinData' => []])

<div id="step7" class="step w-full inactive-right">
    <div class="text-center">
        <p class="text-lg text-gray-700 mb-6">Please review your information and submit your check-in when
            ready.
        </p>
        <div class="bg-white p-4 rounded-lg mb-6 border border-green border-opacity-40">
            <h3 class="font-bold text-lg">Check-in Receipt</h3>
            <x-check-in-summary :checkinData="$checkinData" />
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
                    class="text-sm text-left text-blue-600 hover:text-blue-800 underline cursor-pointer">
                    Read Terms Again
                </button>
            </div>
            <p class="text-xs text-gray-500">By submitting this check-in, you agree to our terms and
                conditions regarding pet care services.</p>
        </div>
        <div class="flex justify-center">
            <button type="button" id="finalSubmit"
                class="px-3 flex text-center justify-center flex-row items-center w-full shadow-md py-2 font-bold rounded-full bg-yellow-second text-gray transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                Submit Check-in
                <iconify-icon class="ml-2 text-xl" icon="fluent:checkmark-20-filled"></iconify-icon>
            </button>
        </div>
    </div>
</div>
