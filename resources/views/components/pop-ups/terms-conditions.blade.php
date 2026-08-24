<div id="termsConditionsPopup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90dvh] overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">{{ $activeTerms->title ?? 'Terms & Conditions' }}</h3>
                <button type="button" id="closeTermsPopup" class="text-gray-400 hover:text-gray-600">
                    <iconify-icon icon="material-symbols:close" class="text-2xl"></iconify-icon>
                </button>
            </div>

            <!-- Content -->
            <div class="relative p-6">
                <div id="termsContent" class="pr-4 overflow-y-auto max-h-[70dvh] pb-12">
                    {{-- Content comes from the DB (terms_and_conditions). Edit it at /petstaff/terms.
                         $activeTerms is supplied by the view composer in AppServiceProvider. --}}
                    {!! $activeTerms->content ?? "" !!}
                </div>

                <!-- Checkbox Container -->
                <div class="absolute w-full flex flex-row flex-wrap bottom-0 bg-white border-t py-4">
                    <div class="flex items-center justify-center w-7/12">
                        <label class="flex items-center">
                            <input type="checkbox" id="termsAccepted" name="termsAccepted" class="mr-3 w-5 h-5"
                                disabled>
                            <span class="text-sm font-medium">I have read the Terms & Conditions</span>
                        </label>
                    </div>
                    <!-- Continue Button -->
                    <div class="flex justify-center">
                        <button type="button" id="continueButton"
                            class="hidden bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200">
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
