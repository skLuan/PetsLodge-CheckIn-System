<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row gap-6 items-center">
            <figure>
                <picture><img height="48" width="48" src="/images/logo-pets-lodge.png" alt="Pets lodge Logo">
                </picture>
            </figure>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Drop-in Confirmation') }}
            </h2>
        </div>
    </x-slot>

    <div class="container px-4 pb-8 max-w-screen-lg mx-auto">
        <div class="py-6">
            <div class="mx-auto mb-6">
                <h1 class="text-2xl font-bold text-center">Check-in Summary</h1>
                <p class="text-lg text-center pt-1 pb-6">Please review your check-in details before proceeding.</p>
            </div>

            <!-- CheckInSummary Component -->
            <x-check-in-summary :checkinData="$checkinData" />

            <div class="mt-8 text-center">
                <p class="text-lg text-gray-600 mb-4">Ready to drop off your pet? Our team has been notified.</p>
                <p class="text-lg">See you later alligator 😄 🐊</p>
            </div>
        </div>
    </div>
</x-app-layout>
