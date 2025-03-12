<?php

?>

@if(isset($flashMessage))
<div class="alert alert-{{ $flashMessage['type'] }}">
    {{ $flashMessage['message'] }}
</div>
@endif