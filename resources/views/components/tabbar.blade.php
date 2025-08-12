<div id="stepNavigation" class="flex justify-between mt-4">
    <button class="px-3 flex flex-row items-center shadow-md py-2 rounded-full bg-gray-lightest" id="prevStep">
        <iconify-icon class="text-3xl" icon="fluent:previous-frame-20-filled"></iconify-icon> Previous</button>
    <button class="px-3 flex flex-row items-center shadow-md py-2 font-bold rounded-full bg-yellow-second text-brown-dark" id="nextStep">
        Next <iconify-icon class="text-3xl" icon="fluent:next-frame-20-filled"></iconify-icon></button>
</div>
<script>
    const progressBar = document.getElementById('stepProgress');
    progressBar.addEventListener('click', function(event) {
        if (event.target.classList.contains('circle')) {
            const stepIndex = Array.from(event.target.parentNode.children).indexOf(event.target);
            showStep(stepIndex);
        }
    });
    const circles = progressBar.querySelectorAll('.circle');
    document.getElementById('prevStep').addEventListener('click', function() {
        // Logic to go to the previous step
        let currentStep = getCurrentStep();
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    document.getElementById('nextStep').addEventListener('click', function() {
        // Logic to go to the next step
        let currentStep = getCurrentStep();
        if (currentStep < 6) {
            showStep(currentStep + 1);
        }
    });

    function getCurrentStep() {
        for (let i = 1; i <= 6; i++) {
            if (document.getElementById(`step${i}`).classList.contains('active')) {
                return i;
            }
        }
        return 1;
    }
    function deactivateAllSteps() {
        for (let i = 1; i <= 6; i++) {
            document.getElementById(`step${i}`).classList.remove('active');
            document.getElementById(`step${i}`).classList.add('inactive-right');
        }
        circles.forEach(circle => {
            circle.classList.remove('active');
            // circle.classList.add('inactive');
        });
    }
    function showStep(step) {
        deactivateAllSteps();
        for (let i = 1; i <= 6; i++) {
            document.getElementById(`step${i}`).classList.toggle('inactive-left', i < step);
            document.getElementById(`step${i}`).classList.toggle('inactive-right', i > step);
            document.getElementById(`step${i}`).classList.toggle('active', i === step);
            circles[i - 1].classList.toggle('active', i-1 < step);
        }
    }
    deactivateAllSteps();
    showStep(1); // Start with the first step active
</script>

</script>