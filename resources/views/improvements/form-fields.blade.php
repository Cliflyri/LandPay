<label class="form-label" for="title">Improvement title</label><input class="form-control mb-3" id="title" name="title" maxlength="150" placeholder="For example, install entrance gate" value="{{old('title')}}" required>
<label class="form-label" for="body">{{$descriptionLabel ?? "Tell us what you're planning"}}</label><textarea class="form-control" id="body" name="body" rows="4" maxlength="10000" required>{{old('body')}}</textarea>
@include('improvements.photos-input')
