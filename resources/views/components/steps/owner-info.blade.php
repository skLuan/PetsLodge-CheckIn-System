@props(['user' => null])

<div id="step1" class="step w-full active z-10 bg-green-lightest">
    <h1 class="text-center">Your Information</h1>
    <p class="text-lg text-center">Please follow the instructions to complete your process</p>
    <x-forms.owner-info :user="$user" />
</div>
