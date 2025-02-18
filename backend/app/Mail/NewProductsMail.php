<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewProductsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newProducts;

    /**
     * Create a new message instance.
     */
    public function __construct(array $newProducts)
    {
        $this->newProducts = $newProducts;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.new_products')
                    ->with(['newProducts' => $this->newProducts]);
    }
}
