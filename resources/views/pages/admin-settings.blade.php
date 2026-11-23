@php
    $avatar = $admin->profile
        ? (\Illuminate\Support\Str::startsWith($admin->profile, ['http://','https://']) ? $admin->profile : asset('storage/'.ltrim($admin->profile,'/')))
        : null;
    $username = \Illuminate\Support\Str::before((string)$admin->email, '@');
    $storeName = $storeSettings->store_name ?? config('app.name', 'Marketly');
    $currency = $storeSettings->currency ?? 'LKR';
@endphp
<div class="admin-settings">
    @if(session('success'))<div class="admin-module-notice">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="form-alert admin-settings-alert">{{ $errors->first() }}</div>@endif
    <nav class="admin-settings-tabs" aria-label="Admin settings sections">
        <a href="#profile">Profile</a><a href="#store-settings">Store Settings</a><a href="#security">Security</a><a href="#notifications">Notifications</a>
    </nav>

    <section class="account-card admin-settings-card" id="profile">
        <div class="admin-settings-section-heading"><div><span class="section-kicker">ACCOUNT</span><h2>Admin Profile</h2><p class="admin-muted">Update the personal details for the signed-in administrator.</p></div></div>
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.profile.update') }}" class="admin-settings-form">@csrf @method('PUT')
            <div class="admin-settings-profile-photo">
                @if($avatar)<img src="{{ $avatar }}" alt="{{ $admin->name }} profile photo">@else<span>{{ strtoupper(mb_substr($admin->name ?: 'A',0,1)) }}</span>@endif
            </div>
            <label class="admin-settings-upload">Profile picture<input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><small>JPG, PNG or WEBP · max 2 MB</small></label>
            <label>Full name<input name="name" value="{{ old('name',$admin->name) }}" required maxlength="255" autocomplete="name"></label>
            <label>Email<input type="email" name="email" value="{{ old('email',$admin->email) }}" required maxlength="255" autocomplete="email"></label>
            <label>Username <small>Derived from the account email; this project has no separate username field.</small><input value="{{ $username }}" readonly></label>
            <label>Phone number<input type="tel" name="phone" value="{{ old('phone',$admin->phone) }}" required maxlength="20" autocomplete="tel"></label>
            <div class="admin-settings-actions"><button class="button-primary" type="submit">Save Changes</button><a class="button-secondary" href="{{ route('admin.section','settings') }}#profile">Cancel</a></div>
        </form>
    </section>

    <section class="account-card admin-settings-card" id="store-settings">
        <div class="admin-settings-section-heading"><div><span class="section-kicker">BUSINESS</span><h2>Store Settings</h2><p class="admin-muted">Store identity and contact information saved for the storefront administration.</p></div></div>
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.profile.store.update') }}" class="admin-settings-form">@csrf @method('PUT')
            @if($storeSettings?->logo_path)<div class="admin-settings-store-logo"><img src="{{ asset('storage/'.$storeSettings->logo_path) }}" alt="Current store logo"><span>Current store logo</span></div>@endif
            <label>Store name<input name="store_name" value="{{ old('store_name',$storeName) }}" required maxlength="150"></label>
            <label>Store email<input type="email" name="email" value="{{ old('email',$storeSettings->email ?? '') }}" maxlength="255"></label>
            <label>Contact number<input type="tel" name="phone" value="{{ old('phone',$storeSettings->phone ?? '') }}" maxlength="30"></label>
            <label>Default currency<select name="currency" required>@foreach(['LKR'=>'LKR — Sri Lankan Rupee','USD'=>'USD — US Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — Pound Sterling'] as $code=>$label)<option value="{{ $code }}" {{ old('currency',$currency)===$code?'selected':'' }}>{{ $label }}</option>@endforeach</select></label>
            <label class="admin-settings-wide">Store address<textarea name="address" rows="3" maxlength="2000">{{ old('address',$storeSettings->address ?? '') }}</textarea></label>
            <label class="admin-settings-upload">Store logo<input type="file" name="store_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><small>JPG, PNG or WEBP · max 3 MB. Leave empty to keep the current logo.</small></label>
            <div class="admin-settings-actions"><button class="button-primary" type="submit">Save Store Settings</button><a class="button-secondary" href="{{ route('admin.section','settings') }}#store-settings">Cancel</a></div>
        </form>
    </section>

    <section class="account-card admin-settings-card" id="security">
        <div class="admin-settings-section-heading"><div><span class="section-kicker">ACCOUNT PROTECTION</span><h2>Security</h2><p class="admin-muted">Verify your current password before choosing a new one.</p></div></div>
        <form method="post" action="{{ route('admin.profile.password.update') }}" class="admin-settings-form admin-settings-security">@csrf @method('PUT')
            <label class="admin-settings-wide">Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>New password<input type="password" name="password" required autocomplete="new-password"><small>At least 12 characters, uppercase and lowercase letters, a number and a symbol.</small></label>
            <label>Confirm new password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <div class="admin-settings-actions"><button class="button-primary" type="submit">Change Password</button></div>
        </form>
        @if($supportsOtherSessions)
            <div class="admin-settings-session-row"><div><strong>Other active sessions</strong><small>Sign out this account from other browser sessions. This keeps your current session active.</small></div><form method="post" action="{{ route('admin.profile.sessions.destroy') }}" data-settings-confirm="Sign out of all other active sessions?">@csrf @method('DELETE')<button class="button-secondary" type="submit">Sign out other sessions</button></form></div>
        @else
            <p class="admin-settings-session-note">Signing out other sessions is unavailable with the current session driver.</p>
        @endif
    </section>

    <section class="account-card admin-settings-card" id="notifications">
        <div class="admin-settings-section-heading"><div><span class="section-kicker">ADMIN UPDATES</span><h2>Notification Preferences</h2><p class="admin-muted">Choose which store events should be enabled for admin notifications.</p></div></div>
        <form method="post" action="{{ route('admin.profile.notifications.update') }}" class="admin-settings-notifications">@csrf @method('PUT')
            @foreach(['notify_new_orders'=>'New order notifications','notify_payments'=>'Payment notifications','notify_low_stock'=>'Low stock notifications','notify_new_customers'=>'New customer registrations'] as $field=>$label)
                <label class="admin-settings-toggle"><span><strong>{{ $label }}</strong><small>{{ ['notify_new_orders'=>'When a customer places an order.','notify_payments'=>'When a payment status is recorded.','notify_low_stock'=>'When stock reaches the low-stock threshold.','notify_new_customers'=>'When a customer account is registered.'][$field] }}</small></span><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" {{ old($field,$storeSettings?->{$field} ?? true) ? 'checked' : '' }}><i aria-hidden="true"></i></label>
            @endforeach
            <div class="admin-settings-actions"><button class="button-primary" type="submit">Save Notification Preferences</button></div>
        </form>
    </section>
</div>
<script>
document.querySelectorAll('[data-settings-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.settingsConfirm))event.preventDefault()}));
</script>
