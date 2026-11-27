@props(['name' => 'petAge', 'id' => 'petAge', 'label' => 'Birth Date'])

<div class="pl-input-container" x-data="datePicker()">
    <label for="{{ $id }}">{{ $label }}
        <input type="text" id="{{ $id }}" name="{{ $name }}" placeholder="Select Birth Date" required readonly
            x-bind:value="formatSelected()"
            x-on:click="open = true"
            x-on:focus="open = true"
            class="cursor-pointer">
    </label>

    {{-- Calendar Modal --}}
    <div x-show="open" x-transition.opacity x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        x-on:click.self="open = false"
        x-on:keydown.escape.window="open = false">
        <div class="bg-white rounded-2xl shadow-xl w-80 p-5" x-on:click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <button type="button" x-on:click="prevMonth()"
                    class="p-1 rounded-lg hover:bg-green-lightest text-green-dark font-bold text-lg cursor-pointer">&larr;</button>
                <div class="flex items-center gap-1">
                    <select x-model="month" x-on:change="clampMonth()"
                        class="text-sm font-bold text-green-dark bg-transparent border border-green-light rounded-md px-1 py-0.5 cursor-pointer focus:outline-none focus:ring-1 focus:ring-green">
                        <template x-for="(name, idx) in monthNames" :key="idx">
                            <option :value="idx" x-text="name" :disabled="year >= new Date().getFullYear() && idx > new Date().getMonth()"></option>
                        </template>
                    </select>
                    <select x-model="year" x-on:change="clampMonth()"
                        class="text-sm font-bold text-green-dark bg-transparent border border-green-light rounded-md px-1 py-0.5 cursor-pointer focus:outline-none focus:ring-1 focus:ring-green">
                        <template x-for="y in years()" :key="y">
                            <option :value="y" x-text="y"></option>
                        </template>
                    </select>
                </div>
                <button type="button" x-on:click="nextMonth()"
                    class="p-1 rounded-lg hover:bg-green-lightest text-green-dark font-bold text-lg cursor-pointer">&rarr;</button>
            </div>

            {{-- Day-of-week headers --}}
            <div class="grid grid-cols-7 text-center text-xs font-bold text-gray mb-1">
                <template x-for="d in dayNames" :key="d">
                    <span x-text="d"></span>
                </template>
            </div>

            {{-- Days grid --}}
            <div class="grid grid-cols-7 text-center gap-y-1">
                {{-- Empty cells before first day --}}
                <template x-for="blank in firstDayOfWeek()" :key="'b'+blank">
                    <span></span>
                </template>
                {{-- Day buttons --}}
                <template x-for="day in daysInMonth()" :key="day">
                    <button type="button"
                        x-on:click="selectDay(day)"
                        x-bind:class="{
                            'bg-green text-white font-bold': isSelected(day),
                            'hover:bg-green-lightest': !isSelected(day),
                            'text-gray-light pointer-events-none': isFuture(day)
                        }"
                        class="w-9 h-9 mx-auto rounded-full text-sm transition-colors duration-200 cursor-pointer"
                        x-bind:disabled="isFuture(day)"
                        x-text="day"></button>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex justify-between mt-4">
                <button type="button" x-on:click="clearDate()"
                    class="text-sm text-gray hover:text-green-dark transition-colors cursor-pointer">Clear</button>
                <button type="button" x-on:click="open = false"
                    class="text-sm font-bold text-green-dark hover:underline cursor-pointer">Done</button>
            </div>
        </div>
    </div>
</div>
