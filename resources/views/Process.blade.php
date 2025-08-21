<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Owner Info') }}
        </h2>
    </x-slot>

    <div class="container px-4 py-8">
        <x-progress.bar />
        <div id="stepContainer" class="py-4 overflow-hidden relative min-h-[568px]">
            <div id="step1" class="step w-full active">
                <h1 class="text-center">Your Information</h1>
                <p class="text-lg text-center">Please follow the instructions to complete your process</p>
                <x-forms.owner-info />
            </div>

            <div id="step2" class="step w-full">
                <h2 class="text-center font-bold">Pet Information</h2>
                <p class="text-lg">General Information of your best friend</p>
                <x-forms.pet-info />
            </div>

            <div id="step3" class="step w-full">
                <h2 class="text-center font-bold">Feeding & medication</h2>
                <p class="text-lg">Please fill out your feeding instructions.</p>
                <form id="feedingAndMedication" action="">

                </form>
            </div>

            <div id="step4" class="step w-full">
                <form id="health" action=""></form>
            </div>

            <div id="step5" class="step w-full">
                <form id="Inventory" action=""></form>
            </div>

            <div id="step6" class="step w-full">
                <form id="Thanks" action=""></form>
            </div>
            <!-- Additional steps will be added here dynamically -->
        </div>
        <x-tabbar />
    </div>
</x-app-layout>
