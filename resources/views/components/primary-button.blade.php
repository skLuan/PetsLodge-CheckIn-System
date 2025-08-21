<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-2 bg-gray-lightest border border-transparent rounded-full font-semibold text-sm text-gray-light uppercase tracking-widest hover:bg-yellow focus:bg-gray-green-dark active:bg-green focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
