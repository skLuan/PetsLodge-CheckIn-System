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
        <x-wizard.header />

        <div id="stepContainer" class="py-4 overflow-hidden relative min-h-[568px]"
             data-session-checkin="{{ htmlspecialchars(json_encode(session('checkin_data', null)), ENT_QUOTES, 'UTF-8') }}"
             data-editing-mode="{{ session('editing_mode', false) ? 'true' : 'false' }}"
             data-editing-check-in-id="{{ session('editing_check_in_id', '') }}">

            <x-steps.owner-info :user="$user ?? null" />
            <x-steps.pet-info :user="$user ?? null" />
            <x-steps.feeding-medication />
            <x-steps.inventory />
            <x-steps.grooming />
            <x-steps.review-submit :checkinData="session('checkin_data', [])" />

        </div>
        <x-pop-ups.feeding-medication />
        <x-pop-ups.terms-conditions />
        <x-pop-ups.confirm-delete />
        <x-tabbar />
    </div>
</x-app-layout>
