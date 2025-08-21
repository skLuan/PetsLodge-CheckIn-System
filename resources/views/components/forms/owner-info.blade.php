<form id="ownerInfoForm" class="">
    <div class="pl-input-container">
        <label class="" for="phone">Phone Number</label>
        <input class="" type="tel" id="phone" name="phone" placeholder="Your Phone Number"
            value="{{ $user->phone ?? request()->get('phone') }}" pattern="[0-9]{10}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Your Name" value="{{ old('name') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Your Email" value="{{ old('email') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Your Address" value="{{ old('address') }}"
            required>
    </div>
    <div class="pl-input-container">
        <label class="" for="city">City</label>
        <input type="text" id="city" name="city" placeholder="Your City" value="{{ old('city') }}" required>
    </div>
    <div class="pl-input-container">
        <label class="" for="zip">Zip Code</label>
        <input type="number" id="zip" name="zip" placeholder="Your Zip Code" value="{{ old('zip') }}"
            required>
    </div>
</form>
