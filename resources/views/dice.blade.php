<h1>Hello, {{ Auth::user()->name }}</h1>
<p>You have {{ $dice_count }} dice left.</p>

@if (session('message'))
    <p>{{ session('message') }}</p>
@endif

<form method="POST" action="{{ route('roll-dice') }}">
    @csrf
    <button type="submit">Roll Dice</button>
</form>
