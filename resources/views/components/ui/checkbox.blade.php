@props(['name', 'value' => null, 'inputClass' => 'mr-3 w-5 h-5'])

<label class="flex items-center cursor-pointer">
    <input type="checkbox" name="{{ $name }}" @if($value !== null) value="{{ $value }}" @endif
        class="{{ $inputClass }}" {{ $attributes }}>
    {{ $slot }}
</label>
