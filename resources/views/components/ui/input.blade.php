@props([
    'label',
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'pattern' => null,
    'required' => false,
    'readonly' => false,
])

@php $inputId = $id ?? $name; @endphp

<div class="pl-input-container">
    <label for="{{ $inputId }}">{{ $label }}
        <input type="{{ $type }}" id="{{ $inputId }}" name="{{ $name }}"
            @if($value !== null) value="{{ $value }}" @endif
            @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif
            @if($pattern !== null) pattern="{{ $pattern }}" @endif
            @if($required) required @endif
            @if($readonly) readonly @endif
            {{ $attributes }}>
    </label>
</div>
