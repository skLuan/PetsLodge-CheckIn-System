<form id="ownerInfoForm" class="">
    @if($user) <script>console.log("User data passed to owner-info:", @json($user));</script> @endif
    <div class="pl-input-container">
        <label class="" for="phone">Phone Number</label>
        <input class="" type="tel" id="phone" name="phone" placeholder="Your Phone Number"
            value="{{ $user?->phone ?? request()->get('phone') }}" pattern="[0-9]{10}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Your Name" value="{{ $user?->name ?? old('name') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Your Email" value="{{ $user?->email ?? old('email') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Your Address" value="{{ $user?->address ?? old('address') }}"
            required>
    </div>
    <div class="pl-input-container">
        <label class="" for="city">City</label>
        <input type="text" id="city" name="city" placeholder="Your City" value="{{ $user?->city ?? old('city') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="zip">Zip Code</label>
        <input type="number" id="zip" name="zip" placeholder="Your Zip Code" value="{{ $user?->zip ?? old('zip') }}"
            required>
    </div>

    <div class="mt-6 pt-4 border-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">🚨 Emergency Contact</h3>
        <div class="pl-input-container">
            <label class="" for="emergencyContactName">Emergency Contact Name</label>
            <input type="text" id="emergencyContactName" name="emergencyContactName" placeholder="Emergency Contact Name">
        </div>
        <div class="pl-input-container">
            <label class="" for="emergencyContactPhone">Emergency Contact Phone</label>
            <input type="tel" id="emergencyContactPhone" name="emergencyContactPhone" placeholder="Emergency Contact Phone" pattern="[0-9]{10}">
        </div>
    </div>
</form>
