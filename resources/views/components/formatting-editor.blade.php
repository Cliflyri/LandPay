@props(['name' => 'body', 'id' => 'body', 'value' => '', 'rows' => 6, 'required' => false])
<div class="formatting-editor" data-formatting-editor data-preview-url="{{route('admin.formatting-preview')}}">
    <div class="formatting-toolbar btn-group btn-group-sm mb-2" role="toolbar" aria-label="Text formatting">
        <button type="button" class="btn btn-outline-secondary" data-format="bold" title="Bold"><strong>B</strong></button>
        <button type="button" class="btn btn-outline-secondary" data-format="italic" title="Italic"><em>I</em></button>
        <button type="button" class="btn btn-outline-secondary text-decoration-underline" data-format="underline" title="Underline">U</button>
        <button type="button" class="btn btn-outline-secondary" data-format="large" title="Larger text">A+</button>
        <button type="button" class="btn btn-outline-secondary" data-format="link" title="Link">Link</button>
        <button type="button" class="btn btn-outline-secondary" data-format="bullets" title="Bulleted list">• List</button>
        <button type="button" class="btn btn-outline-secondary" data-format="numbers" title="Numbered list">1. List</button>
        <button type="button" class="btn btn-outline-secondary" data-format="preview" title="Preview">Preview</button>
    </div>
    <textarea class="form-control" id="{{$id}}" name="{{$name}}" rows="{{$rows}}" maxlength="10000" @required($required)>{{$value}}</textarea>
    <div class="form-text">Formatting: bold, italic, underline, larger text, links, and lists.</div>
    <div class="formatted-text formatting-preview border rounded p-3 mt-2 d-none" data-formatting-preview></div>
</div>