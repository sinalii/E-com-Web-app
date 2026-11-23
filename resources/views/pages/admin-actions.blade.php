<div class="admin-row-actions">
    @if($editable)<a class="text-link" href="{{ route('admin.section', [$section, 'edit' => $row->id]) }}">Edit</a>@endif
    @if($deletable)<form method="post" action="{{ route('admin.resource.destroy', [$section, $row->id]) }}" onsubmit="return confirm('Remove this record?')">@csrf @method('DELETE')<button class="admin-delete" type="submit">Delete</button></form>@endif
</div>
