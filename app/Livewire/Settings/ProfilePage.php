<?php

namespace App\Livewire\Settings;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProfilePage extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name ?? '';
    }

    public function save(): void
    {
        $this->validate();

        auth()->user()->update(['name' => $this->name]);

        session()->flash('success', __('profile.updated'));
        $this->redirectRoute('settings.profile');
    }

    public function render(): View
    {
        return view('livewire.settings.profile-page', [
            'user' => auth()->user(),
        ]);
    }
}
