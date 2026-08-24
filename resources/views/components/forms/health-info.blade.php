<form id="healthInfoForm" action="">
    <div class="pl-input-container">
        <h3 class="text-lg font-bold text-gray mb-2">Did you notice unusual health behavior such as Vomiting, Diarrhea, Heart Conditions, Physical Condition, Seizure, others?</h3>
        <div class="flex items-center gap-6">
            <x-ui.radio name="unusualHealthBehavior" value="yes" input-class="mr-2">Yes</x-ui.radio>
            <x-ui.radio name="unusualHealthBehavior" value="no" input-class="mr-2">No</x-ui.radio>
        </div>
    </div>

    <div class="pl-input-container conditional-health-details" style="display: none;">
        <label for="healthBehaviorDetails">Which?
            <input type="text" id="healthBehaviorDetails" name="healthBehaviorDetails" placeholder="Diarrhea, Vomiting, etc.">
        </label>
    </div>

    <div class="pl-input-container">
        <label for="warnings"><span class="!text-red-700">Warnings</span></label>
        <p class="text-sm text-gray mb-2">Health Matters, Special Care, Behavioral (Friendly with other dogs, Food Aggressive, Anxiety, Others)</p>
        <textarea class="w-full" id="warnings" name="warnings" rows="3" placeholder="Enter any warnings or special notes"></textarea>
    </div>
</form>