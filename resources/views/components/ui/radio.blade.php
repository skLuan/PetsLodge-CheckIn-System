@props(['name', 'value', 'inputClass' => 'mr-3 w-5 h-5'])

<label class="flex items-center cursor-pointer">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" class="{{ $inputClass }}" {{ $attributes }}>
    {{ $slot }}
</label>
