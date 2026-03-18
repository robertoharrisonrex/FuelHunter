<?php

namespace App\Livewire;

use Livewire\Component;

class FeedbackForm extends Component
{
    public string $name    = '';
    public string $email   = '';
    public string $message = '';
    public bool   $submitted = false;

    protected array $rules = [
        'name'    => 'required|min:2|max:100',
        'email'   => 'required|email|max:255',
        'message' => 'required|min:10|max:2000',
    ];

    protected array $messages = [
        'name.required'    => 'Please enter your name.',
        'email.required'   => 'Please enter your email address.',
        'email.email'      => 'Please enter a valid email address.',
        'message.required' => 'Please enter a message.',
        'message.min'      => 'Your message must be at least 10 characters.',
    ];

    public function submit(): void
    {
        $this->validate();
        // Feedback captured — email/storage can be wired up here
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.feedback-form');
    }
}
