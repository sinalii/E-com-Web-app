<div class="admin-row-actions" style="margin-top:7px;flex-wrap:wrap">
    @if($customer->is_active)
        <form method="post" action="{{ route('admin.customer.status', $customer->id) }}" onsubmit="return confirm('Deactivate this customer account?')">@csrf @method('PATCH')<input type="hidden" name="action" value="deactivate"><button class="button-secondary" type="submit">Deactivate</button></form>
    @else
        <form method="post" action="{{ route('admin.customer.status', $customer->id) }}" onsubmit="return confirm('Activate this customer account?')">@csrf @method('PATCH')<input type="hidden" name="action" value="activate"><button class="button-secondary" type="submit">Activate</button></form>
    @endif
    @if($customer->is_blocked)
        <form method="post" action="{{ route('admin.customer.status', $customer->id) }}" onsubmit="return confirm('Unblock this customer account?')">@csrf @method('PATCH')<input type="hidden" name="action" value="unblock"><button class="button-secondary" type="submit">Unblock</button></form>
    @else
        <form method="post" action="{{ route('admin.customer.status', $customer->id) }}" onsubmit="return confirm('Block this customer account?')">@csrf @method('PATCH')<input type="hidden" name="action" value="block"><button class="button-secondary" type="submit">Block</button></form>
    @endif
</div>
