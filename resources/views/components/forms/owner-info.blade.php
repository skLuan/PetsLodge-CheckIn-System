<form id="ownerInfoForm" class="">
    @if ($user)
        <script>
            console.log("User data passed to owner-info:", @json($user));
        </script>
    @endif

    <x-ui.input label="Phone Number" name="phone" type="tel" placeholder="Your Phone Number"
        :value="$user?->phone ?? request()->get('phone')" pattern="[0-9]{10}" required />

    <x-ui.input label="Name and Last name" name="name" placeholder="Your Name"
        :value="$user?->name ?? old('name')" required />

    <x-ui.input label="Email" name="email" type="email" placeholder="Your Email"
        :value="$user?->email ?? old('email')" required />

    <x-ui.input label="Address" name="address" placeholder="Your Address"
        :value="$user?->address ?? old('address')" required />

    <x-ui.input label="City" name="city" placeholder="Your City"
        :value="$user?->city ?? old('city')" required />

    <x-ui.input label="State" name="state" placeholder="Your State"
        :value="$user?->state ?? old('state')" required />

    <x-ui.input label="Zip Code" name="zip" type="number" placeholder="Your Zip Code"
        :value="$user?->zip ?? old('zip')" required />

    <div class="mt-6 pt-4 border-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">🚨 Emergency Contact</h3>
        <x-ui.input label="Emergency Contact Name" name="emergencyContactName" placeholder="Emergency Contact Name" />
        <x-ui.input label="Emergency Contact Phone" name="emergencyContactPhone" type="tel" placeholder="Emergency Contact Phone" pattern="[0-9]{10}" />
    </div>
</form>
