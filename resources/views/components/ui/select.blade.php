@props(['label', 'name', 'id' => null, 'required' => false])

@php $inputId = $id ?? $name; @endphp

<div class="pl-input-container pr-3">
    <label class="w-full justify-center items-center flex" for="{{ $inputId }}">{{ $label }}
        <select id="{{ $inputId }}" name="{{ $name }}" @if($required) required @endif
            {{ $attributes->merge(['class' => 'ml-auto rounded-md cursor-pointer']) }}>
            {{ $slot }}
        </select>
    </label>
</div>
