<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewProductsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $products;
    public $subject;
    public $shop;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($products, $shop, $subject = 'New Products Available')
    {
        $this->products = $products;
        $this->subject = $subject;
        $this->shop = $shop;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.new_products')
                    ->subject($this->subject);
    }
}