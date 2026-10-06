<fieldset class="closing-owner" data-owner>
    <legend>Proposed owner</legend>
    <label>Full legal name <span class="text-muted">(including middle name, if present)</span>
        <input class="form-control" name="owners[{{ $ownerIndex }}][name]" value="{{ $owner['name'] ?? '' }}" maxlength="255" required autocomplete="name">
    </label>
    <label>Full legal address
        <textarea class="form-control" name="owners[{{ $ownerIndex }}][address]" maxlength="2000" required>{{ $owner['address'] ?? '' }}</textarea>
    </label>
    <label>Mailing address <span class="text-muted">(optional, if different)</span>
        <textarea class="form-control" name="owners[{{ $ownerIndex }}][mailing_address]" maxlength="2000">{{ $owner['mailing_address'] ?? '' }}</textarea>
    </label>
    <label>Are you married?
        <select class="form-select" name="owners[{{ $ownerIndex }}][married]" required>
            <option value="">Please choose</option>
            <option value="yes" @selected(($owner['married'] ?? '') === 'yes')>Yes</option>
            <option value="no" @selected(($owner['married'] ?? '') === 'no')>No</option>
        </select>
    </label>
    <p class="small">We ask because marital status may affect the paperwork required for your property &mdash; <strong>even if a spouse is not listed on the paperwork.</strong></p>
    @if($ownerIndex !== 0)<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-owner>Remove this proposed owner</button>@endif
</fieldset>
