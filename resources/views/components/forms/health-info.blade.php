<form id="healthInfoForm" action="">
    <div class="pl-input-container">
        <h3 class="text-lg font-bold text-gray mb-2">Did you notice unusual health behavior such as Vomiting, Diarrhea, Heart Conditions, Physical Condition, Seizure, others?</h3>
        <div class="flex items-center">
            <label class="mr-6">
                <input type="radio" name="unusualHealthBehavior" value="yes" class="mr-2">
                Yes
            </label>
            <label>
                <input type="radio" name="unusualHealthBehavior" value="no" class="mr-2">
                No
            </label>
        </div>
    </div>

    <div class="pl-input-container conditional-health-details" style="display: none;">
        <label for="healthBehaviorDetails">Which?
            <input type="text" id="healthBehaviorDetails" name="healthBehaviorDetails" placeholder="Diarrhea">
        </label>
    </div>

    <div class="pl-input-container">
        <label for="warnings">Warnings</label>
        <p class="text-sm text-gray mb-2">Health Matters, Special Care, Behavioral (Friendly with other dogs, Food Aggressive, Anxiety, Others)</p>
        <textarea class="w-full" id="warnings" name="warnings" rows="3" placeholder="Enter any warnings or special notes"></textarea>
    </div>

    <div class="pl-input-container">
        <h3 class="text-lg font-bold text-gray mb-2">Want grooming before picking up?</h3>
        <div class="flex flex-col gap-4">
            <label>
                <input type="checkbox" name="grooming[]" value="bath" class="mr-2">
                Bath
            </label>
            <label>
                <input type="checkbox" name="grooming[]" value="nails" class="mr-2">
                Nails
            </label>
            <label>
                <input type="checkbox" name="grooming[]" value="grooming" class="mr-2">
                Grooming
            </label>
            <label>
                <input type="checkbox" name="grooming[]" value="no" class="mr-2">
                No
            </label>
        </div>
    </div>

    <div class="pl-input-container conditional-grooming-notes" style="display: none;">
        <label for="groomingDetails">Grooming instructions & notes:
            <textarea class="w-full font-normal" id="groomingDetails" name="groomingDetails" rows="3" placeholder="Enter grooming instructions"></textarea>
        </label>
    </div>
</form>