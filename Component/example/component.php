<?php

class UserProfile extends Component
{
    protected function mount(): void
    {
        // Custom initialization logic
        $this->data['username'] = $this->fetchUsername();
    }
}

// In a view file (components/user-profile.ace.php)
@component('components/user-profile')
    <div class="profile">
        <h1>{{ $username }}</h1>
        {{ $slots['__default__'] }}
    </div>
@endcomponent